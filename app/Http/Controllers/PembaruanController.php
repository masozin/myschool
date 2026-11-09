<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ZipArchive;

/**
 * Pembaruan aplikasi dari dalam aplikasi (untuk hosting bersama
 * tanpa SSH): Admin mengunggah paket pembaruan (.zip berisi
 * update-manifest.json + file-file yang berubah), lalu halaman
 * ini yang mengerjakan sisanya:
 *
 *  1. Memvalidasi paket (manifest wajib ada; path menyusup dan
 *     path terlarang ditolak).
 *  2. Membackup file-file yang akan ditimpa/dihapus ke
 *     storage/app/pembaruan/backup-<waktu>.zip.
 *  3. Menyalin file baru secara atomik (tulis .tmp lalu rename)
 *     dan menghapus file yang tercantum pada manifest "hapus".
 *  4. Menjalankan migrasi database dan membersihkan cache.
 *  5. Mencatat versi terpasang dan riwayat pembaruan.
 *
 * Yang TIDAK pernah disentuh: .env, folder vendor, folder
 * storage, dan symlink public/storage. Paket yang memuat salah
 * satunya ditolak — pembaruan semacam itu harus dipasang manual.
 * Backup yang dibuat hanya mencakup FILE; backup database tetap
 * tanggung jawab Admin (ekspor via phpMyAdmin).
 */
class PembaruanController extends Controller
{
  /** Batas keamanan paket. */
  private int $maksFile = 5000;
  private int $maksUkuranTotal = 209715200; // 200 MB tak terkompresi

  private function folderKerja(): string
  {
    return storage_path('app/pembaruan');
  }

  // ── Halaman ────────────────────────────────────────────────────

  public function index()
  {
    File::ensureDirectoryExists($this->folderKerja());

    $terpasang = $this->bacaJson($this->folderKerja() . '/terpasang.json');
    $riwayat   = $this->bacaJson($this->folderKerja() . '/riwayat.json') ?? [];

    $daftarBackup = collect(glob($this->folderKerja() . '/backup-*.zip') ?: [])
      ->map(fn($p) => [
        'nama'  => basename($p),
        'ukuran' => File::size($p),
        'waktu' => date('d M Y H:i', File::lastModified($p)),
      ])
      ->sortByDesc('nama')
      ->values();

    return view('content.pembaruan.index', compact('terpasang', 'riwayat', 'daftarBackup'));
  }

  // ── Terapkan paket pembaruan ───────────────────────────────────

  public function terapkan(Request $request)
  {
    $request->validate([
      'paket' => ['required', 'file', 'mimes:zip', 'max:102400'],
      'konfirmasi_backup_db' => ['accepted'],
    ], [
      'konfirmasi_backup_db.accepted' => 'Centang dulu pernyataan backup database sebelum menerapkan pembaruan.',
    ]);

    File::ensureDirectoryExists($this->folderKerja());
    $capWaktu = now()->format('Ymd-His');
    $filePaket = $this->folderKerja() . '/masuk-' . $capWaktu . '.zip';
    $folderStaging = $this->folderKerja() . '/staging-' . $capWaktu;

    $request->file('paket')->move($this->folderKerja(), basename($filePaket));

    try {
      [$manifest, $daftarFile, $daftarHapus] = $this->periksaPaket($filePaket);
    } catch (\Throwable $e) {
      File::delete($filePaket);
      return back()->with('gagal', $e->getMessage());
    }

    // 1) Backup file yang akan ditimpa / dihapus
    $namaBackup = 'backup-' . $capWaktu . '.zip';
    try {
      $this->buatBackup($this->folderKerja() . '/' . $namaBackup, $daftarFile, $daftarHapus);
    } catch (\Throwable $e) {
      File::delete($filePaket);
      return back()->with('gagal', 'Backup gagal dibuat, pembaruan dibatalkan: ' . $e->getMessage());
    }

    // 2) Ekstrak ke staging, salin atomik, hapus yang perlu dihapus
    $tercopy = 0;
    $terhapus = 0;
    try {
      $zip = new ZipArchive();
      $zip->open($filePaket);
      File::ensureDirectoryExists($folderStaging);
      $zip->extractTo($folderStaging);
      $zip->close();

      foreach ($daftarFile as $rel) {
        $sumber = $folderStaging . '/' . $rel;
        $tujuan = base_path($rel);
        File::ensureDirectoryExists(dirname($tujuan));
        $sementara = $tujuan . '.tmp-pembaruan';
        File::copy($sumber, $sementara);
        File::move($sementara, $tujuan);
        $tercopy++;
      }

      foreach ($daftarHapus as $rel) {
        if (File::exists(base_path($rel))) {
          File::delete(base_path($rel));
          $terhapus++;
        }
      }
    } catch (\Throwable $e) {
      File::deleteDirectory($folderStaging);
      File::delete($filePaket);
      return back()->with('gagal', 'Penyalinan file gagal di tengah jalan: ' . $e->getMessage()
        . ' Sebagian file mungkin sudah berubah — gunakan tombol Pulihkan pada backup ' . $namaBackup . ' untuk mengembalikan.');
    }
    File::deleteDirectory($folderStaging);
    File::delete($filePaket);

    // 3) Migrasi + bersihkan cache (kegagalan di sini tidak
    //    menggagalkan file yang sudah terpasang; dilaporkan apa adanya)
    $laporan = [
      'versi' => $manifest['versi'],
      'file' => $tercopy,
      'dihapus' => $terhapus,
      'backup' => $namaBackup,
      'migrasi' => null,
      'peringatan' => [],
    ];

    try {
      Artisan::call('migrate', ['--force' => true]);
      $laporan['migrasi'] = trim(Artisan::output()) ?: 'Tidak ada migrasi baru.';
    } catch (\Throwable $e) {
      $laporan['peringatan'][] = 'Migrasi gagal: ' . $e->getMessage();
    }

    $this->bersihkanCache($laporan['peringatan']);

    // 4) Catat versi terpasang + riwayat
    $catatan = [
      'versi' => $manifest['versi'],
      'deskripsi' => $manifest['deskripsi'] ?? '',
      'dibuat' => $manifest['dibuat'] ?? '',
      'diterapkan_pada' => now()->format('d M Y H:i'),
      'file' => $tercopy,
      'dihapus' => $terhapus,
      'backup' => $namaBackup,
      'status' => empty($laporan['peringatan']) ? 'berhasil' : 'berhasil dengan peringatan',
    ];
    $this->tulisJson($this->folderKerja() . '/terpasang.json', $catatan);
    $this->tambahRiwayat($catatan);

    return back()->with('laporan', $laporan);
  }

  // ── Pulihkan dari backup ───────────────────────────────────────

  public function pulihkan(Request $request)
  {
    $request->validate([
      'backup' => ['required', 'string'],
    ]);

    $nama = basename($request->backup);
    if (!preg_match('/^backup-\d{8}-\d{6}\.zip$/', $nama)) {
      return back()->with('gagal', 'Nama backup tidak sah.');
    }
    $fileBackup = $this->folderKerja() . '/' . $nama;
    if (!File::exists($fileBackup)) {
      return back()->with('gagal', 'File backup tidak ditemukan.');
    }

    $capWaktu = now()->format('Ymd-His');
    $folderStaging = $this->folderKerja() . '/staging-pulih-' . $capWaktu;

    try {
      $zip = new ZipArchive();
      $zip->open($fileBackup);

      $isiManifest = $zip->getFromName('backup-manifest.json');
      $meta = $isiManifest ? json_decode($isiManifest, true) : null;
      $fileBaru = $meta['baru'] ?? [];

      File::ensureDirectoryExists($folderStaging);
      $zip->extractTo($folderStaging);
      $zip->close();

      // Kembalikan isi file lama (termasuk file yang dulu dihapus
      // oleh pembaruan — isinya ada di dalam backup ini)
      $dikembalikan = 0;
      foreach (File::allFiles($folderStaging) as $f) {
        $rel = str_replace('\\', '/', $f->getRelativePathname());
        if ($rel === 'backup-manifest.json') {
          continue;
        }
        $tujuan = base_path($rel);
        File::ensureDirectoryExists(dirname($tujuan));
        $sementara = $tujuan . '.tmp-pembaruan';
        File::copy($f->getPathname(), $sementara);
        File::move($sementara, $tujuan);
        $dikembalikan++;
      }

      // File yang dulu DIBUAT oleh pembaruan harus hilang lagi
      $dihapus = 0;
      foreach ($fileBaru as $rel) {
        if ($this->pathAman($rel) && File::exists(base_path($rel))) {
          File::delete(base_path($rel));
          $dihapus++;
        }
      }

      File::deleteDirectory($folderStaging);
    } catch (\Throwable $e) {
      File::deleteDirectory($folderStaging);
      return back()->with('gagal', 'Pemulihan gagal: ' . $e->getMessage());
    }

    $peringatan = [];
    $this->bersihkanCache($peringatan);

    $this->tambahRiwayat([
      'versi' => 'Pemulihan ' . $nama,
      'deskripsi' => 'Mengembalikan file dari backup. Migrasi database TIDAK ikut dibatalkan.',
      'diterapkan_pada' => now()->format('d M Y H:i'),
      'file' => $dikembalikan,
      'dihapus' => $dihapus,
      'backup' => $nama,
      'status' => 'rollback',
    ]);

    return back()->with('laporan', [
      'versi' => 'Pemulihan dari ' . $nama,
      'file' => $dikembalikan,
      'dihapus' => $dihapus,
      'backup' => $nama,
      'migrasi' => 'Migrasi database tidak dibatalkan oleh pemulihan file.',
      'peringatan' => $peringatan,
    ]);
  }

  // ── Bantuan internal ───────────────────────────────────────────

  /**
   * Memeriksa paket dan mengembalikan [manifest, daftarFile,
   * daftarHapus]. Melempar Exception berpesan jelas bila paket
   * tidak sah atau menyentuh path terlarang.
   */
  private function periksaPaket(string $filePaket): array
  {
    $zip = new ZipArchive();
    if ($zip->open($filePaket) !== true) {
      throw new \Exception('File ZIP tidak dapat dibuka. Pastikan file paket utuh.');
    }

    $isiManifest = $zip->getFromName('update-manifest.json');
    if ($isiManifest === false) {
      $zip->close();
      throw new \Exception('Ini bukan paket pembaruan yang sah: update-manifest.json tidak ditemukan di dalam ZIP.');
    }

    $manifest = json_decode($isiManifest, true);
    if (!is_array($manifest) || empty($manifest['versi']) || empty($manifest['dibuat'])) {
      $zip->close();
      throw new \Exception('Isi update-manifest.json tidak lengkap (wajib ada versi dan dibuat).');
    }

    $daftarFile = [];
    $totalUkuran = 0;
    for ($i = 0; $i < $zip->numFiles; $i++) {
      $stat = $zip->statIndex($i);
      $nama = str_replace('\\', '/', $stat['name']);

      if (str_ends_with($nama, '/')) {
        continue; // entri folder
      }
      if ($nama === 'update-manifest.json') {
        continue; // metadata, bukan file aplikasi
      }
      if (!$this->pathAman($nama)) {
        $zip->close();
        throw new \Exception('Paket memuat path yang tidak aman: ' . $nama);
      }
      if ($this->pathTerlarang($nama)) {
        $zip->close();
        throw new \Exception('Paket menyentuh area terlarang (' . $nama . '). Pembaruan yang mengubah .env, vendor, atau storage harus dipasang manual.');
      }

      $daftarFile[] = $nama;
      $totalUkuran += $stat['size'];
    }
    $zip->close();

    if (count($daftarFile) > $this->maksFile) {
      throw new \Exception('Paket memuat terlalu banyak file (' . count($daftarFile) . ').');
    }
    if ($totalUkuran > $this->maksUkuranTotal) {
      throw new \Exception('Ukuran paket setelah diekstrak terlalu besar.');
    }

    $daftarHapus = [];
    foreach (($manifest['hapus'] ?? []) as $rel) {
      $rel = str_replace('\\', '/', (string) $rel);
      if (!$this->pathAman($rel)) {
        throw new \Exception('Daftar hapus memuat path yang tidak aman: ' . $rel);
      }
      if ($this->pathTerlarang($rel)) {
        throw new \Exception('Daftar hapus menyentuh area terlarang: ' . $rel);
      }
      $daftarHapus[] = $rel;
    }

    return [$manifest, $daftarFile, $daftarHapus];
  }

  /**
   * Membuat backup ZIP berisi versi saat ini dari semua file
   * yang akan ditimpa atau dihapus, plus backup-manifest.json
   * yang mencatat file mana yang BARU (belum ada) — file baru
   * tidak punya isi lama, jadi saat pemulihan ia cukup dihapus.
   */
  private function buatBackup(string $fileBackup, array $daftarFile, array $daftarHapus): void
  {
    $zip = new ZipArchive();
    if ($zip->open($fileBackup, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new \Exception('folder storage/app/pembaruan tidak dapat ditulis.');
    }

    $baru = [];
    foreach (array_merge($daftarFile, $daftarHapus) as $rel) {
      $sumber = base_path($rel);
      if (File::exists($sumber)) {
        $zip->addFile($sumber, $rel);
      } elseif (in_array($rel, $daftarFile, true)) {
        $baru[] = $rel;
      }
    }

    $zip->addFromString('backup-manifest.json', json_encode([
      'dibuat' => now()->toDateTimeString(),
      'baru' => $baru,
    ], JSON_PRETTY_PRINT));
    $zip->close();
  }

  /** Menolak path absolut dan path yang keluar dari root aplikasi. */
  private function pathAman(string $rel): bool
  {
    if ($rel === '' || str_starts_with($rel, '/') || str_contains($rel, "\0")) {
      return false;
    }
    foreach (explode('/', $rel) as $segmen) {
      if ($segmen === '..') {
        return false;
      }
    }
    return true;
  }

  /** Area yang tidak boleh disentuh paket pembaruan. */
  private function pathTerlarang(string $rel): bool
  {
    return $rel === '.env'
      || str_starts_with($rel, 'vendor/')
      || str_starts_with($rel, 'storage/')
      || str_starts_with($rel, '.git/')
      || $rel === 'public/storage'
      || str_starts_with($rel, 'public/storage/');
  }

  private function bersihkanCache(array &$peringatan): void
  {
    foreach (['view:clear', 'config:clear', 'route:clear', 'cache:clear'] as $perintah) {
      try {
        Artisan::call($perintah);
      } catch (\Throwable $e) {
        $peringatan[] = 'Perintah ' . $perintah . ' gagal: ' . $e->getMessage();
      }
    }
    if (function_exists('opcache_reset')) {
      @opcache_reset();
    }
  }

  private function bacaJson(string $path): ?array
  {
    if (!File::exists($path)) {
      return null;
    }
    $data = json_decode(File::get($path), true);
    return is_array($data) ? $data : null;
  }

  private function tulisJson(string $path, array $data): void
  {
    File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
  }

  private function tambahRiwayat(array $catatan): void
  {
    $path = $this->folderKerja() . '/riwayat.json';
    $riwayat = $this->bacaJson($path) ?? [];
    array_unshift($riwayat, $catatan);
    $this->tulisJson($path, array_slice($riwayat, 0, 20));
  }
}

...[truncated 4471 chars]