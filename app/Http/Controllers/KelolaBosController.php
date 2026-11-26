<?php

namespace App\Http\Controllers;

use App\Models\BosAnggaran;
use App\Models\BosKasNota;
use App\Models\BosKasUmum;
use App\Models\BosNamaAlias;
use App\Models\BosRapbs;
use App\Models\BosRapbsPeriode;
use App\Models\BosRefAcuanBarang;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefRekening;
use App\Models\BosSinkronLog;
use App\Models\BosSinkronToken;
use App\Models\Ptk;
use App\Models\Sekolah;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Kelola BOS (fitur: kelola-bos) — tampilan read-only cermin
 * data ARKAS. Halaman: Kertas Kerja (bos_rapbs, dengan
 * pemilih versi RKAS serta — Fase 1.6 — jadwal bulanan dari
 * bos_rapbs_periode dan status realisasi BKU per baris), BKU
 * (bos_kas_umum), Perbandingan Versi (selisih dua versi
 * kertas kerja — lembar kerja pergeseran), dan Referensi
 * kode (barang/kegiatan/rekening). Cermin TIDAK diubah
 * dari sini; ia datang dari agen sinkron di laptop ARKAS.
 * Satu-satunya penulisan adalah nama alias (Fase 1.7) —
 * lapisan kerja milik pengguna, terpisah dari cermin.
 * Fase 1.12 menambah satu penulisan lagi yang juga
 * lapisan kerja: pembuatan token agen sinkron dari web
 * (sebelumnya hanya lewat perintah `bos:token`).
 * Fase 1.10: Referensi Kegiatan dibatasi ke jenjang
 * sekolah (dari pemakaian rapbs, cadangan jenjang
 * tabel_sekolah) dan halaman-halaman dibuka dengan
 * bawaan tahun berjalan + BOS Reguler + versi terakhir
 * disetujui bila parameternya tidak dikirim.
 *
 * Operator Sekolah, Kepala Sekolah & Bendahara (hanya
 * membaca) melihat sekolahnya; Admin memantau lewat
 * pemilih sekolah (pola Surat Keluar).
 */
class KelolaBosController extends Controller
{
  /** Nama bulan untuk pemilih jadwal (id_periode ARKAS 81–92). */
  private const NAMA_BULAN = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ];

  /** Jenis BKU belanja (Kas Keluar dkk.) untuk realisasi. */
  private const JENIS_BELANJA = [4, 15, 24, 35];

  /**
   * Pemetaan jenis BKU (id_ref_bku ARKAS) untuk kartu dana di halaman
   * BKU: id => tanda (+1 menambah, -1 mengurangi). Kolom saldo di cermin
   * BKU tidak bertanda, jadi arah tiap jenis ditetapkan di sini — sudah
   * diuji terhadap saldo bawaan antarbulan ARKAS (data 2024–2026 cocok
   * di semua bulan). Ubah di sini bila ada jenis baru.
   *
   * Tunai : 9 Saldo Awal Tunai (+), 12 Pergeseran Tunai (+),
   *         4 Kas Keluar (−)
   * Bank  : 8 Saldo Awal Bank (+), 2 Terima Dana BOS (+), 6 Bunga Bank (+),
   *         7 Pajak Bunga (−), 3 Tarik Tunai (−),
   *         15 Kas Keluar Non Tunai (−)
   *
   * Penting: "Saldo Awal" (8/9) bukan saldo awal tahun, melainkan saldo
   * penutup bulan sebelumnya yang dibawa ARKAS ke tiap bulan, per
   * anggaran (sumber dana). Menjumlahkannya lintas bulan salah; yang
   * dipakai hanya baris saldo awal TERAKHIR tiap anggaran, ditambah
   * transaksi sesudahnya (lihat hitungKartuDana).
   *
   * Pajak : jenis 10 Pajak Belanja Terima — hanya ditampilkan sebagai
   *         jumlah pajak, tidak ikut menghitung dana.
   */
  private const DANA_TUNAI = [9 => 1, 12 => 1, 4 => -1];
  private const DANA_BANK = [8 => 1, 2 => 1, 6 => 1, 7 => -1, 3 => -1, 15 => -1];
  private const JENIS_PAJAK = 10;
  /** Jenis yang dikenal tetapi tidak memengaruhi saldo kas (pajak terima/setor). */
  private const DANA_DIABAIKAN = [10, 11];

  /** Konteks sekolah aktif (pola yang sama dengan Surat Keluar). */
  private function sekolahKonteksIdNullable(): ?int
  {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
      if (request()->filled('sekolah_id')) {
        session(['admin_sekolah_id' => request()->integer('sekolah_id')]);
      }

      $sekolahId = session('admin_sekolah_id') ?? $user->ptk?->sekolah_id;

      if (!$sekolahId && Sekolah::count() === 1) {
        $sekolahId = Sekolah::value('id');
      }

      return $sekolahId ? (int) $sekolahId : null;
    }

    return $user->ptk?->sekolah_id ? (int) $user->ptk->sekolah_id : null;
  }

  private function konteksUmum(Request $request): array
  {
    $user = Auth::user();
    $sekolahId = $this->sekolahKonteksIdNullable();

    return [
      'sekolahId' => $sekolahId,
      'daftarSekolah' => $user->hasRole('Admin')
        ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
        : collect(),
      'sekolah' => $sekolahId ? Sekolah::with('detailTerbaru')->find($sekolahId) : null,
      'sinkronTerakhir' => $sekolahId ? BosSinkronLog::terakhirUntuk($sekolahId) : null,
    ];
  }

  /** Arah urutan yang aman dari request. */
  private function arah(Request $request): string
  {
    return strtolower((string) $request->input('dir')) === 'asc' ? 'asc' : 'desc';
  }

  // ── Nama alias (Fase 1.7, lapisan kerja) ──────────────
  //
  // Alias adalah nama pendek buatan pengguna untuk nama
  // asli ARKAS yang panjang. Peta alias untuk nama-nama di
  // satu halaman diambil dengan SATU query (whereIn atas
  // nama yang tampil), bukan query per baris.

  /** @return array<string, string> peta nama_sumber => alias */
  private function petaAlias(int $sekolahId, $daftarNama): array
  {
    $nama = collect($daftarNama)
      ->filter(fn($n) => is_string($n) && $n !== '')
      ->unique()
      ->values();
    if ($nama->isEmpty()) {
      return [];
    }
    return BosNamaAlias::where('sekolah_id', $sekolahId)
      ->whereIn('nama_sumber', $nama)
      ->pluck('alias', 'nama_sumber')
      ->all();
  }

  /** Alias hanya boleh ditulis Admin & Operator Sekolah. */
  private function bisaTulisAlias(): bool
  {
    return Auth::user()->hasAnyRole(['Admin', 'Operator Sekolah']);
  }

  /**
   * Simpan / hapus alias. Alias terisi → updateOrCreate;
   * alias kosong → baris dihapus (tampilan kembali ke
   * nama asli dari ARKAS).
   */
  public function simpanAlias(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Konteks sekolah belum dipilih — alias tidak disimpan.');
    }

    $data = $request->validate([
      'nama_sumber' => ['required', 'string', 'max:500'],
      'alias' => ['nullable', 'string', 'max:255'],
    ]);

    $alias = trim((string) ($data['alias'] ?? ''));
    if ($alias === '') {
      BosNamaAlias::where('sekolah_id', $sekolahId)
        ->where('nama_sumber', $data['nama_sumber'])
        ->delete();
      return back()->with('success', 'Alias dihapus — nama asli dari ARKAS ditampilkan lagi.');
    }

    BosNamaAlias::updateOrCreate(
      ['sekolah_id' => $sekolahId, 'nama_sumber' => $data['nama_sumber']],
      ['alias' => $alias]
    );
    return back()->with('success', 'Alias "' . $alias . '" disimpan.');
  }

  private function daftarTahun(int $sekolahId): array
  {
    return BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->distinct()
      ->orderByDesc('tahun_anggaran')
      ->pluck('tahun_anggaran')
      ->map(fn($t) => (int) $t)
      ->all();
  }

  private function daftarSumberDana(int $sekolahId)
  {
    return BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->select('id_ref_sumber_dana', 'nama_sumber_dana')
      ->distinct()
      ->orderBy('nama_sumber_dana')
      ->get();
  }

  // ── Bawaan halaman (Fase 1.10) ──────────────────────
  //
  // Halaman Kelola BOS dibuka dengan pilihan yang paling
  // sering dipakai: tahun berjalan dan — untuk Kertas
  // Kerja & Perbandingan — BOS Reguler serta versi
  // terakhir disetujui. Bawaan hanya berlaku bila
  // parameternya TIDAK dikirim sama sekali; pilihan
  // eksplisit pengguna (termasuk memilih "semua" yang
  // mengirim parameter kosong) selalu menang.

  /** Tahun bawaan dari daftar tahun yang tersedia. */
  private function tahunBawaan(array $daftarTahun): ?int
  {
    if (!$daftarTahun) {
      return null;
    }
    $tahunIni = (int) now()->format('Y');
    return in_array($tahunIni, $daftarTahun, true) ? $tahunIni : max($daftarTahun);
  }

  /**
   * Tahun efektif sebuah halaman: nilai parameter bila
   * diisi; null (semua tahun) bila parameter dikirim
   * kosong secara eksplisit; bawaan bila parameter
   * tidak dikirim sama sekali.
   */
  private function tahunEfektif(Request $request, array $daftarTahun): ?int
  {
    if ($request->filled('tahun')) {
      return $request->integer('tahun');
    }
    if ($request->has('tahun')) {
      return null;
    }
    return $this->tahunBawaan($daftarTahun);
  }

  /**
   * Sumber dana bawaan untuk satu tahun dari kelompok
   * versi: BOS Reguler (id_ref_sumber_dana 1) bila ada,
   * selain itu sumber pertama yang tersedia tahun itu.
   */
  private function sumberBawaan(array $kelompok, ?int $tahun): ?int
  {
    $ada = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      $ada[] = $k['sumber'];
    }
    if (!$ada) {
      return null;
    }
    return in_array(1, $ada, true) ? 1 : $ada[0];
  }

  // ── Jenjang sekolah (Fase 1.10) ─────────────────────
  //
  // bentuk_pendidikan_id sekolah ini, untuk membatasi
  // Referensi Kegiatan ke jenjangnya sendiri. Urutan
  // penurunan:
  //  1. Dari pemakaian nyata: varian ref_kode yang
  //     benar-benar dipakai baris rapbs sekolah ini —
  //     varian dibedakan lewat tahun anggaran baris
  //     (id_ref_tahun_anggaran) dan sumber dana
  //     anggarannya; bentuk yang paling banyak dipakai
  //     menang. Ini bukti terkuat.
  //  2. Cadangan: tabel_sekolah.jenjang dipetakan ke id
  //     bentuk ARKAS — hanya pasangan yang terbukti dari
  //     data ('SD' → 5). Jenjang lain sengaja TIDAK
  //     dipetakan: ARKAS tidak menyediakan tabel padanan
  //     dan id bentuknya belum terverifikasi.
  //  3. Keduanya kosong → null (tanpa pembatasan).

  private function bentukSekolah(int $sekolahId): ?int
  {
    $baris = BosRefKode::query()
      ->from('bos_ref_kode as rk')
      ->join('bos_rapbs as rp', function ($j) {
        $j->on('rp.id_ref_kode', '=', 'rk.id_ref_kode')
          ->on('rp.id_ref_tahun_anggaran', '=', 'rk.tahun');
      })
      ->join('bos_anggaran as ag', function ($j) {
        $j->on('ag.id_arkas', '=', 'rp.id_anggaran_arkas')
          ->on('ag.sekolah_id', '=', 'rp.sekolah_id')
          ->on('ag.id_ref_sumber_dana', '=', 'rk.sumber_dana_id');
      })
      ->where('rp.sekolah_id', $sekolahId)
      ->where('rp.soft_delete', false)
      ->whereNotNull('rk.bentuk_pendidikan_id')
      ->selectRaw('rk.bentuk_pendidikan_id as bentuk, COUNT(*) as terpakai')
      ->groupBy('rk.bentuk_pendidikan_id')
      ->orderByDesc('terpakai')
      ->first();
    if ($baris) {
      return (int) $baris->bentuk;
    }

    $jenjang = strtoupper(trim((string) (Sekolah::find($sekolahId)?->jenjang ?? '')));
    return ['SD' => 5][$jenjang] ?? null;
  }

  // ── Lingkup referensi (Fase 1.11) ───────────────────
  //
  // Halaman referensi dibatasi ke yang benar-benar bisa
  // dibelanjakan sekolah ini: kegiatan menurut jenjang
  // (Fase 1.10) DAN sumber dananya; barang & rekening
  // menurut rekening yang pernah dipakai kertas
  // kerjanya. Cermin tetap lengkap — semua ini murni
  // saringan tampil, dan selalu ada jalan melepasnya
  // (?semua=1).

  /**
   * Sumber dana yang dimiliki sekolah ini: sumber dari
   * anggarannya pada tahun terpilih; bila tahun itu
   * tidak punya anggaran, sumber dari semua tahun.
   * Hasil kosong berarti tanpa pembatasan sumber.
   */
  private function sumberSekolah(int $sekolahId, ?int $tahun): array
  {
    $ambil = fn(?int $th) => BosAnggaran::where('sekolah_id', $sekolahId)
      ->when($th !== null, fn($x) => $x->where('tahun_anggaran', $th))
      ->distinct()->pluck('id_ref_sumber_dana')
      ->map(fn($s) => (int) $s)->all();
    $sumber = $tahun !== null ? $ambil($tahun) : [];
    return $sumber ?: $ambil(null);
  }

  /**
   * Kode rekening yang pernah dipakai baris kertas
   * kerja sekolah ini, semua tahun. Baris ter-soft-
   * delete ikut dihitung: pada cermin ARKAS, soft-
   * delete di rapbs umumnya menandai salinan versi
   * yang tergantikan — rekeningnya tetap pernah resmi
   * dianggarkan sekolah ini. Hasil kosong berarti
   * tanpa pembatasan rekening.
   */
  private function rekeningTerpakai(int $sekolahId): array
  {
    return BosRapbs::where('sekolah_id', $sekolahId)
      ->whereNotNull('kode_rekening')
      ->distinct()->pluck('kode_rekening')->all();
  }

  // ── Versi kertas kerja ─────────────────────────────────
  //
  // Di ARKAS, setiap revisi RKAS adalah salinan utuh kertas
  // kerja dalam baris anggaran tersendiri. Versi-versi per
  // (tahun, sumber dana) diurutkan menurut tanggal buat
  // ARKAS (arkas_create_date, cadangan is_revisi) dan
  // dilabeli berurutan: RKAS Awal, Revisi 1, Revisi 2, ...

  /** @return array<string, array{tahun:int, sumber:int, nama_sumber:?string, versi:\Illuminate\Support\Collection}> */
  private function kelompokVersi(int $sekolahId): array
  {
    $semua = BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->orderBy('tahun_anggaran')
      ->orderBy('id_ref_sumber_dana')
      ->orderByRaw('CASE WHEN arkas_create_date IS NULL THEN 1 ELSE 0 END')
      ->orderBy('arkas_create_date')
      ->orderBy('is_revisi')
      ->orderBy('id')
      ->get();

    $kelompok = [];
    foreach ($semua as $a) {
      $kunci = $a->tahun_anggaran . '|' . $a->id_ref_sumber_dana;
      if (!isset($kelompok[$kunci])) {
        $kelompok[$kunci] = [
          'tahun' => (int) $a->tahun_anggaran,
          'sumber' => (int) $a->id_ref_sumber_dana,
          'nama_sumber' => $a->nama_sumber_dana,
          'versi' => collect(),
        ];
      }
      $kelompok[$kunci]['versi']->push($a);
    }
    foreach ($kelompok as $k) {
      foreach ($k['versi'] as $i => $a) {
        $a->label_versi = $i === 0 ? 'RKAS Awal' : 'Revisi ' . $i;
      }
    }
    return $kelompok;
  }

  /**
   * Tentukan id_arkas anggaran yang dipakai untuk cakupan
   * (tahun, sumber dana). $versiParam: null/kosong = bawaan
   * (versi disetujui terakhir per kelompok, cadangan versi
   * terakhir), 'semua' = semua versi, selain itu id_arkas
   * satu versi tertentu (kelompok lain tetap bawaan).
   *
   * @return string[]
   */
  private function pilihVersiIds(array $kelompok, ?int $tahun, ?int $sumber, ?string $versiParam): array
  {
    $ids = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      if ($sumber !== null && $k['sumber'] !== $sumber) {
        continue;
      }
      $daftar = $k['versi'];
      if ($versiParam === 'semua') {
        foreach ($daftar as $a) {
          $ids[] = $a->id_arkas;
        }
        continue;
      }
      if ($versiParam) {
        $cocok = $daftar->firstWhere('id_arkas', $versiParam);
        if ($cocok) {
          $ids[] = $cocok->id_arkas;
          continue;
        }
      }
      $disetujui = $daftar->filter(fn($a) => (int) $a->is_approve === 1)->last();
      $pilih = $disetujui ?: $daftar->last();
      if ($pilih) {
        $ids[] = $pilih->id_arkas;
      }
    }
    return $ids;
  }

  /** Pilihan untuk select Versi (kelompok dalam cakupan tahun/sumber). */
  private function pilihanVersi(array $kelompok, ?int $tahun, ?int $sumber): array
  {
    $pilihan = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      if ($sumber !== null && $k['sumber'] !== $sumber) {
        continue;
      }
      foreach ($k['versi'] as $a) {
        $pilihan[] = [
          'id_arkas' => $a->id_arkas,
          'label' => $k['tahun'] . ' · ' . ($k['nama_sumber'] ?? 'Sumber #' . $k['sumber'])
            . ' — ' . $a->label_versi,
          'disetujui' => (int) $a->is_approve === 1,
        ];
      }
    }
    return $pilihan;
  }

  // ── Kertas Kerja ─────────────────────────────────────────

  public function kertasKerja(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.kertas-kerja', $konteks + [
        'baris' => null,
        'totalJumlah' => 0,
        'ringkasan' => collect(),
        'daftarTahun' => [],
        'daftarSumberDana' => collect(),
        'daftarKegiatan' => collect(),
        'daftarVersi' => [],
        'daftarBulan' => self::NAMA_BULAN,
        'bulan' => (int) now()->format('n'),
        'bulanPotong' => (int) now()->format('n'),
        'tahunAcuan' => (int) now()->format('Y'),
        'tampilan' => 'posisi',
        'totalRencana' => 0,
        'statusFilter' => '',
        'kartu' => ['terjadwal' => 0.0, 'realisasi' => 0.0, 'sisa' => 0.0],
        'urut' => 'kegiatan',
        'arah' => 'asc',
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'tahunTerpilih' => null,
        'sumberTerpilih' => null,
      ]);
    }

    // Bawaan Fase 1.10: tanpa parameter tahun → tahun
    // berjalan (cadangan tahun terbesar yang ada); tanpa
    // parameter sumber dana → BOS Reguler bila ada pada
    // tahun efektif. Parameter yang dikirim kosong secara
    // eksplisit ("semua") tetap dihormati.
    $daftarTahun = $this->daftarTahun($sekolahId);
    $tahunReq = $this->tahunEfektif($request, $daftarTahun);
    $kelompok = $this->kelompokVersi($sekolahId);
    $sumberReq = $request->filled('sumber_dana')
      ? $request->integer('sumber_dana')
      : ($request->has('sumber_dana') ? null : $this->sumberBawaan($kelompok, $tahunReq));
    $versiParam = $request->filled('versi') ? (string) $request->input('versi') : null;

    // ── Tiga tampilan Kertas Kerja (Fase 1.8) ──
    // Halaman ini punya tiga tampilan:
    //  - posisi  : "Posisi s.d. Bulan Ini" — semantik
    //    kumulatif Fase 1.6, tetapi titik potong bulannya
    //    OTOMATIS: tahun berjalan → bulan ini, tahun
    //    lampau → Desember, tahun depan → belum ada
    //    bulan berjalan (potong 0). Tanpa pemilih bulan.
    //  - belanja : "Bisa Dibelanjakan" — agregat kumulatif
    //    yang sama dengan titik potong yang sama, tetapi
    //    hanya baris yang masih punya sisa untuk
    //    dibelanjakan (jadwal kumulatif > 0 dan sisa > 0),
    //    termasuk tunggakan bulan-bulan lalu yang belum
    //    masuk BKU.
    //  - rencana : "Rencana Bulanan" — satu-satunya
    //    tampilan berpemilih bulan: rencana anggaran bulan
    //    pilihan (bulan depan, bulan-bulan berikutnya,
    //    maupun bulan yang sudah lewat) dengan tanda
    //    sudah/belum dibelanjakan per baris.
    $tampilan = in_array($request->input('tampilan'), ['posisi', 'belanja', 'rencana'], true)
      ? (string) $request->input('tampilan')
      : 'posisi';

    $tahunIni = (int) now()->format('Y');
    $tahunAcuan = $tahunReq ?? $tahunIni;
    $bulanPotong = $tahunAcuan === $tahunIni
      ? (int) now()->format('n')
      : ($tahunAcuan < $tahunIni ? 12 : 0);

    if ($tampilan === 'rencana') {
      $bulanBawaan = $tahunAcuan === $tahunIni
        ? min((int) now()->format('n') + 1, 12)
        : 1;
      $bulan = $request->filled('bulan')
        ? max(1, min(12, $request->integer('bulan')))
        : $bulanBawaan;
    } else {
      $bulan = $bulanPotong;
    }

    $statusFilter = in_array($request->input('status'), ['sudah', 'sebagian', 'belum', 'belum_terjadwal'], true)
      ? (string) $request->input('status')
      : '';
    if ($tampilan === 'belanja') {
      // Pada tampilan belanja status tersirat dari
      // saringannya sendiri (masih ada sisa) — saringan
      // status disembunyikan dan tidak diterapkan.
      $statusFilter = '';
    }

    $idsUtama = $this->pilihVersiIds($kelompok, $tahunReq, $sumberReq, $versiParam);
    // Ringkasan per sumber dana tidak ikut saringan sumber
    // dana, tetapi mengikuti pilihan versi & tahun.
    $idsRingkasan = $this->pilihVersiIds($kelompok, $tahunReq, null, $versiParam);

    // ref_kode ARKAS punya banyak varian per (id, tahun)
    // untuk sumber dana/bentuk pendidikan berbeda; uraian
    // & id_kode-nya sama, jadi join memakai subquery
    // distinct agar baris rapbs tidak berlipat.
    $refKodeDistinct = BosRefKode::query()
      ->select('id_ref_kode', 'tahun')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode', 'tahun');

    $dasar = fn() => BosRapbs::query()
      ->from('bos_rapbs')
      ->join('bos_anggaran', function ($j) {
        $j->on('bos_anggaran.id_arkas', '=', 'bos_rapbs.id_anggaran_arkas')
          ->on('bos_anggaran.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoinSub($refKodeDistinct, 'bos_ref_kode', function ($j) {
        $j->on('bos_ref_kode.id_ref_kode', '=', 'bos_rapbs.id_ref_kode')
          ->on('bos_ref_kode.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->leftJoin('bos_ref_rekening', function ($j) {
        $j->on('bos_ref_rekening.kode_rekening', '=', 'bos_rapbs.kode_rekening')
          ->on('bos_ref_rekening.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->where('bos_rapbs.sekolah_id', $sekolahId)
      ->where('bos_rapbs.soft_delete', false);

    $saring = function ($q, array $idsVersi) use ($request) {
      $q->whereIn('bos_rapbs.id_anggaran_arkas', $idsVersi ?: ['__tidak_ada__'])
        ->when($request->filled('kegiatan'), fn($x) => $x->where('bos_rapbs.id_ref_kode', $request->input('kegiatan')))
        ->when($request->filled('cari'), function ($x) use ($request) {
          $cari = '%' . $request->input('cari') . '%';
          $x->where(function ($y) use ($cari) {
            $y->where('bos_rapbs.uraian', 'like', $cari)
              ->orWhere('bos_rapbs.kode_rekening', 'like', $cari)
              ->orWhere('bos_ref_rekening.nama_rekening', 'like', $cari)
              ->orWhere('bos_ref_kode.uraian_kode', 'like', $cari);
          });
        });
      return $q;
    };

    // ── Agregat jadwal & realisasi per baris ──
    // Jadwal: jumlah bos_rapbs_periode (baris bulanan saja,
    // id_periode 81–92; baris Triwulan 1–4 diabaikan untuk
    // tampilan bulanan) milik baris rapbs ini. Tampilan
    // posisi & belanja menjumlah Januari s.d. bulan potong
    // otomatis; tampilan rencana hanya bulan pilihannya.
    // Realisasi: saldo BKU belanja (jenis 4/15/24/35) yang
    // terhubung ke baris periode milik baris rapbs ini
    // lewat bos_kas_umum.id_rapbs_periode — jadi realisasi
    // selalu menempel ke versi tempat BKU diinput di ARKAS.
    // Setiap pembayaran BKU menempel ke SATU baris periode
    // bulanan tertentu (jadwal bulan mundur boleh dibayar
    // terlambat; jadwal bulan depan tidak bisa dibayar
    // lebih dulu), jadi:
    //  - posisi & belanja: realisasi adalah seluruh
    //    pembayaran baris ini, tanggal terakhir sebaris
    //    penuh (Fase 1.6/1.7).
    //  - rencana: realisasi & tanggal terakhir hanya dari
    //    pembayaran yang terhubung ke baris periode bulan
    //    pilihan (atribusi per bulan, Fase 1.7/1.8).
    if ($tampilan === 'rencana') {
      $periodeDari = 80 + $bulan;
      $periodeSampai = 80 + $bulan;
    } else {
      $periodeDari = 81;
      $periodeSampai = 80 + $bulanPotong;
    }

    $buatSubJadwal = fn() => BosRapbsPeriode::query()
      ->select('sekolah_id', 'id_rapbs_arkas')
      ->selectRaw('SUM(jumlah) as jadwal')
      ->where('soft_delete', false)
      ->whereBetween('id_periode', [$periodeDari, $periodeSampai])
      ->groupBy('sekolah_id', 'id_rapbs_arkas');

    $buatSubRealisasi = fn() => BosKasUmum::query()
      ->from('bos_kas_umum as bku16')
      ->join('bos_rapbs_periode as per16', function ($j) {
        $j->on('per16.id_arkas', '=', 'bku16.id_rapbs_periode')
          ->on('per16.sekolah_id', '=', 'bku16.sekolah_id');
      })
      ->select('per16.sekolah_id', 'per16.id_rapbs_arkas')
      ->selectRaw($tampilan === 'rencana'
        ? 'SUM(CASE WHEN per16.id_periode = ' . $periodeSampai . ' THEN bku16.saldo ELSE 0 END) as realisasi, MAX(CASE WHEN per16.id_periode = ' . $periodeSampai . ' THEN bku16.tanggal_transaksi END) as realisasi_terakhir'
        : 'SUM(bku16.saldo) as realisasi, MAX(bku16.tanggal_transaksi) as realisasi_terakhir')
      ->where('bku16.soft_delete', false)
      ->whereIn('bku16.id_ref_bku', self::JENIS_BELANJA)
      ->whereNotNull('bku16.id_rapbs_periode')
      ->groupBy('per16.sekolah_id', 'per16.id_rapbs_arkas');

    // ── Jadwal tertua yang belum lunas (Fase 1.8) ──
    // Untuk tampilan belanja: bulan jadwal paling awal
    // (Januari s.d. bulan potong) yang realisasi per baris
    // periodenya masih kurang dari jumlah jadwal baris
    // periode itu. Realisasi per baris periode dihitung
    // dulu sebagai subquery agar join tidak menggandakan
    // baris periode.
    $realisasiPerPeriode = BosKasUmum::query()
      ->select('sekolah_id', 'id_rapbs_periode')
      ->selectRaw('SUM(saldo) as real_baris')
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->whereNotNull('id_rapbs_periode')
      ->groupBy('sekolah_id', 'id_rapbs_periode');

    $buatSubTertua = fn() => BosRapbsPeriode::query()
      ->from('bos_rapbs_periode as perT')
      ->leftJoinSub($realisasiPerPeriode, 'reaT', function ($j) {
        $j->on('reaT.id_rapbs_periode', '=', 'perT.id_arkas')
          ->on('reaT.sekolah_id', '=', 'perT.sekolah_id');
      })
      ->select('perT.sekolah_id', 'perT.id_rapbs_arkas')
      ->selectRaw('MIN(CASE WHEN COALESCE(reaT.real_baris, 0) < perT.jumlah THEN perT.id_periode END) as tertua_periode')
      ->where('perT.soft_delete', false)
      ->whereBetween('perT.id_periode', [81, 80 + $bulanPotong])
      ->groupBy('perT.sekolah_id', 'perT.id_rapbs_arkas');

    $denganAgg = function ($q) use ($buatSubJadwal, $buatSubRealisasi, $buatSubTertua, $tampilan) {
      $q = $q
        ->leftJoinSub($buatSubJadwal(), 'jad', function ($j) {
          $j->on('jad.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('jad.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        })
        ->leftJoinSub($buatSubRealisasi(), 'rea', function ($j) {
          $j->on('rea.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('rea.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        });
      if ($tampilan === 'belanja') {
        $q = $q->leftJoinSub($buatSubTertua(), 'ter', function ($j) {
          $j->on('ter.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('ter.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        });
      }
      return $q;
    };

    // Kelas status per baris dari jadwal & realisasi.
    $kasusStatus = "CASE
      WHEN COALESCE(jad.jadwal, 0) > 0 AND COALESCE(rea.realisasi, 0) >= COALESCE(jad.jadwal, 0) THEN 'sudah'
      WHEN COALESCE(rea.realisasi, 0) > 0 AND COALESCE(rea.realisasi, 0) < COALESCE(jad.jadwal, 0) THEN 'sebagian'
      WHEN COALESCE(rea.realisasi, 0) = 0 AND COALESCE(jad.jadwal, 0) > 0 THEN 'belum'
      ELSE 'belum_terjadwal' END";

    $totalJumlah = (float) $saring($dasar(), $idsUtama)->sum('bos_rapbs.jumlah');

    // Kartu ringkasan jadwal/realisasi: cakupan sama dengan
    // tabel (versi, kegiatan, pencarian) tetapi TANPA
    // saringan status — status adalah saringan tampilan
    // atas kolom hitungan ini, bukan cakupan datanya.
    // Pengecualian Fase 1.8: tampilan belanja memang
    // DIDEFINISIKAN oleh saringannya (hanya baris bersisa),
    // jadi kartu mengikuti himpunan yang tampil — kartu
    // "Bisa Dibelanjakan" adalah sisa himpunan itu. Pada
    // tampilan rencana kartu hanya menjumlah baris yang
    // punya jadwal di bulan pilihan (total rencana bulan
    // itu serta realisasinya).
    $saringBelanja = 'COALESCE(jad.jadwal, 0) > 0 AND COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) > 0';
    $aggKartu = $denganAgg($saring($dasar(), $idsUtama))
      ->when($tampilan === 'belanja', fn($x) => $x->whereRaw("({$saringBelanja})"))
      ->when($tampilan === 'rencana', fn($x) => $x->whereNotNull('jad.jadwal'))
      ->selectRaw('COALESCE(SUM(jad.jadwal), 0) as terjadwal, COALESCE(SUM(rea.realisasi), 0) as realisasi')
      ->first();
    $kartu = [
      'terjadwal' => (float) ($aggKartu->terjadwal ?? 0),
      'realisasi' => (float) ($aggKartu->realisasi ?? 0),
    ];
    $kartu['sisa'] = $kartu['terjadwal'] - $kartu['realisasi'];

    // Ringkasan per sumber dana: saringan yang sama kecuali
    // saringan sumber dana itu sendiri.
    $ringkasan = $saring($dasar(), $idsRingkasan)
      ->groupBy('bos_anggaran.id_ref_sumber_dana', 'bos_anggaran.nama_sumber_dana')
      ->selectRaw('bos_anggaran.id_ref_sumber_dana, bos_anggaran.nama_sumber_dana, COUNT(*) as baris, SUM(bos_rapbs.jumlah) as total')
      ->orderBy('bos_anggaran.nama_sumber_dana')
      ->get();

    $kolomUrut = [
      'kegiatan' => 'bos_ref_kode.uraian_kode',
      'rekening' => 'bos_rapbs.kode_rekening',
      'uraian' => 'bos_rapbs.uraian',
      'volume' => 'bos_rapbs.volume',
      'harga' => 'bos_rapbs.harga_satuan',
      'jumlah' => 'bos_rapbs.jumlah',
      'jadwal' => 'jadwal',
      'realisasi' => 'realisasi',
      'sisa' => 'sisa',
      'tertua' => 'tertua_periode',
    ];
    $urut = $kolomUrut[$request->input('urut')] ?? null;
    $urutKunci = $urut ? $request->input('urut') : 'kegiatan';
    $arah = $this->arah($request);

    $baris = $denganAgg($saring($dasar(), $idsUtama))
      ->when($tampilan === 'belanja', fn($x) => $x->whereRaw("({$saringBelanja})"))
      ->when($tampilan === 'rencana', fn($x) => $x->whereNotNull('jad.jadwal'))
      ->when($statusFilter !== '', fn($x) => $x->whereRaw("({$kasusStatus}) = ?", [$statusFilter]))
      ->select([
        'bos_rapbs.*',
        'bos_anggaran.tahun_anggaran',
        'bos_anggaran.nama_sumber_dana',
        'bos_ref_kode.id_kode as kode_kegiatan',
        'bos_ref_kode.uraian_kode as nama_kegiatan',
      ])
      ->selectRaw('bos_ref_rekening.nama_rekening as nama_rekening')
      ->selectRaw('COALESCE(jad.jadwal, 0) as jadwal, COALESCE(rea.realisasi, 0) as realisasi')
      ->selectRaw('rea.realisasi_terakhir as realisasi_terakhir')
      ->selectRaw('COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) as sisa')
      ->selectRaw($kasusStatus . ' as status_kelas')
      ->when($tampilan === 'belanja', fn($x) => $x->selectRaw('ter.tertua_periode as tertua_periode'))
      ->when(
        $tampilan === 'belanja' && !$urut,
        fn($x) => $x->orderBy('tertua_periode', 'asc')->orderBy('bos_rapbs.uraian', 'asc'),
        fn($x) => $x->orderBy($urut ?? 'bos_ref_kode.uraian_kode', $urut ? $arah : 'asc')
      )
      ->orderBy('bos_rapbs.kode_rekening')
      ->orderBy('bos_rapbs.urutan')
      ->paginate(25)
      ->withQueryString();

    $daftarKegiatan = BosRefKode::query()
      ->whereNotNull('id_ref_kode')
      ->whereIn('id_ref_kode', function ($q) use ($sekolahId) {
        $q->select('id_ref_kode')->from('bos_rapbs')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->select('id_ref_kode')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode')
      ->orderBy('uraian_kode')
      ->get();

    return view('content.kelola-bos.kertas-kerja', $konteks + [
      'baris' => $baris,
      'totalJumlah' => $totalJumlah,
      'ringkasan' => $ringkasan,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunReq,
      'sumberTerpilih' => $sumberReq,
      'daftarSumberDana' => $this->daftarSumberDana($sekolahId),
      'daftarKegiatan' => $daftarKegiatan,
      'daftarVersi' => $this->pilihanVersi($kelompok, $tahunReq, $sumberReq),
      'daftarBulan' => self::NAMA_BULAN,
      'bulan' => $bulan,
      'bulanPotong' => $bulanPotong,
      'tahunAcuan' => $tahunAcuan,
      'tampilan' => $tampilan,
      'totalRencana' => $tampilan === 'rencana' ? $kartu['terjadwal'] : 0,
      'statusFilter' => $statusFilter,
      'kartu' => $kartu,
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => $this->petaAlias($sekolahId, $baris->getCollection()->pluck('uraian')),
      'bisaTulisAlias' => $this->bisaTulisAlias(),
    ]);
  }

  // ── Perbandingan Versi ───────────────────────────────────

  public function perbandingan(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    $kosong = [
      'hasil' => null,
      'daftarTahun' => $sekolahId ? $this->daftarTahun($sekolahId) : [],
      'daftarSumberDana' => $sekolahId ? $this->daftarSumberDana($sekolahId) : collect(),
      'daftarVersi' => [],
      'tahunTerpilih' => null,
      'sumberTerpilih' => null,
      'versiATerpilih' => '',
      'versiBTerpilih' => '',
    ];
    if (!$sekolahId) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }

    // Bawaan Fase 1.10 sama seperti Kertas Kerja: tahun
    // berjalan & BOS Reguler bila parameternya tidak
    // dikirim sama sekali.
    $tahunReq = $this->tahunEfektif($request, $kosong['daftarTahun']);
    $kelompok = $this->kelompokVersi($sekolahId);
    $sumberReq = $request->filled('sumber_dana')
      ? $request->integer('sumber_dana')
      : ($request->has('sumber_dana') ? null : $this->sumberBawaan($kelompok, $tahunReq));
    $kosong['daftarVersi'] = $this->pilihanVersi($kelompok, $tahunReq, $sumberReq);
    $kosong['tahunTerpilih'] = $tahunReq;
    $kosong['sumberTerpilih'] = $sumberReq;

    // Bawaan versi (Fase 1.10): A = versi disetujui
    // terakhir pada (tahun, sumber) efektif; B = versi
    // terbaru yang berbeda dari A — memunculkan draf
    // yang sedang dikerjakan (mis. Revisi 3) — cadangan
    // versi sebelum A, terakhir A itu sendiri. Bawaan
    // hanya bila parameternya tidak dikirim sama sekali.
    $grupScope = null;
    foreach ($kelompok as $k) {
      if (($tahunReq === null || $k['tahun'] === $tahunReq)
        && ($sumberReq === null || $k['sumber'] === $sumberReq)) {
        $grupScope = $k;
        break;
      }
    }
    if ($request->has('versi_a')) {
      $idA = (string) $request->input('versi_a', '');
    } else {
      $idA = '';
      if ($grupScope) {
        $disetujui = $grupScope['versi']->filter(fn($a) => (int) $a->is_approve === 1)->last();
        $pilihA = $disetujui ?: $grupScope['versi']->last();
        $idA = $pilihA ? (string) $pilihA->id_arkas : '';
      }
    }
    if ($request->has('versi_b')) {
      $idB = (string) $request->input('versi_b', '');
    } else {
      // Kelompok pemilik A (bisa berbeda dari kelompok
      // cakupan bila A dipilih eksplisit).
      $grupB = $grupScope;
      foreach ($kelompok as $k) {
        if ($k['versi']->contains(fn($a) => (string) $a->id_arkas === $idA)) {
          $grupB = $k;
          break;
        }
      }
      $idB = '';
      if ($grupB) {
        $daftar = $grupB['versi'];
        $terbaru = $daftar->last();
        if ($terbaru && (string) $terbaru->id_arkas !== $idA) {
          $idB = (string) $terbaru->id_arkas;
        } else {
          $idx = $daftar->search(fn($a) => (string) $a->id_arkas === $idA);
          $idB = ($idx !== false && $idx > 0)
            ? (string) $daftar[$idx - 1]->id_arkas
            : $idA;
        }
      }
    }
    $kosong['versiATerpilih'] = $idA;
    $kosong['versiBTerpilih'] = $idB;
    if (!$idA || !$idB || $idA === $idB) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }

    $versiA = BosAnggaran::where('sekolah_id', $sekolahId)->where('id_arkas', $idA)->first();
    $versiB = BosAnggaran::where('sekolah_id', $sekolahId)->where('id_arkas', $idB)->first();
    if (!$versiA || !$versiB) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }
    // Label versi dari kelompoknya masing-masing.
    foreach ($kelompok as $k) {
      foreach ($k['versi'] as $a) {
        if ($a->id_arkas === $idA) {
          $versiA->label_versi = $a->label_versi;
        }
        if ($a->id_arkas === $idB) {
          $versiB->label_versi = $a->label_versi;
        }
      }
    }

    $agregat = function (string $idArkas) use ($sekolahId) {
      return BosRapbs::where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_anggaran_arkas', $idArkas)
        ->groupBy('id_ref_kode', 'kode_rekening', 'uraian')
        ->selectRaw('id_ref_kode, kode_rekening, uraian, SUM(volume) as volume, SUM(jumlah) as jumlah, COUNT(*) as baris')
        ->get()
        ->keyBy(fn($r) => $r->id_ref_kode . '|' . $r->kode_rekening . '|' . $r->uraian);
    };
    $aggA = $agregat($idA);
    $aggB = $agregat($idB);

    $tahunBanding = (int) $versiA->tahun_anggaran;
    $petaKegiatan = BosRefKode::where('tahun', $tahunBanding)
      ->whereNotNull('id_ref_kode')
      ->select('id_ref_kode')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode')
      ->get()
      ->keyBy('id_ref_kode');
    $petaRekening = BosRefRekening::where('tahun', $tahunBanding)
      ->pluck('nama_rekening', 'kode_rekening');

    $hitung = ['berubah' => 0, 'baru' => 0, 'hilang' => 0, 'tetap' => 0];
    $perKegiatan = [];
    foreach ($aggA->keys()->merge($aggB->keys())->unique() as $kunci) {
      $a = $aggA->get($kunci);
      $b = $aggB->get($kunci);
      $acuan = $a ?: $b;
      $volA = $a ? (float) $a->volume : 0.0;
      $volB = $b ? (float) $b->volume : 0.0;
      $jumA = $a ? (float) $a->jumlah : 0.0;
      $jumB = $b ? (float) $b->jumlah : 0.0;
      $kelas = match (true) {
        $a === null => 'baru',
        $b === null => 'hilang',
        $volA !== $volB || $jumA !== $jumB => 'berubah',
        default => 'tetap',
      };
      $hitung[$kelas]++;

      $idKegiatan = (string) $acuan->id_ref_kode;
      if (!isset($perKegiatan[$idKegiatan])) {
        $perKegiatan[$idKegiatan] = [
          'id_kode' => $petaKegiatan->get($idKegiatan)->id_kode ?? '',
          'uraian_kode' => $petaKegiatan->get($idKegiatan)->uraian_kode ?? 'Kegiatan lain-lain',
          'totalA' => 0.0,
          'totalB' => 0.0,
          'baris' => [],
        ];
      }
      $perKegiatan[$idKegiatan]['totalA'] += $jumA;
      $perKegiatan[$idKegiatan]['totalB'] += $jumB;
      if ($kelas !== 'tetap') {
        $perKegiatan[$idKegiatan]['baris'][] = [
          'uraian' => $acuan->uraian,
          'kode_rekening' => $acuan->kode_rekening,
          'nama_rekening' => $petaRekening->get($acuan->kode_rekening),
          'volume_a' => $volA,
          'volume_b' => $volB,
          'jumlah_a' => $jumA,
          'jumlah_b' => $jumB,
          'selisih' => $jumB - $jumA,
          'kelas' => $kelas,
        ];
      }
    }
    // Urutkan kegiatan menurut nama; baris di dalamnya menurut uraian.
    $perKegiatan = collect($perKegiatan)
      ->sortBy('uraian_kode')
      ->map(function ($k) {
        $k['baris'] = collect($k['baris'])->sortBy('uraian')->values()->all();
        return $k;
      })
      ->values()
      ->all();

    $totalA = (float) $aggA->sum('jumlah');
    $totalB = (float) $aggB->sum('jumlah');

    $kosong['hasil'] = [
      'versiA' => $versiA,
      'versiB' => $versiB,
      'totalA' => $totalA,
      'totalB' => $totalB,
      'selisih' => $totalB - $totalA,
      'hitung' => $hitung,
      'perKegiatan' => $perKegiatan,
    ];
    return view('content.kelola-bos.perbandingan', $konteks + $kosong);
  }

  // ── Referensi: Kode Barang ───────────────────────────────

  public function referensiBarang(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-barang', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
        'daftarBlok' => [],
        'urut' => 'nama',
        'arah' => 'asc',
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'tahunTerpilih' => null,
        'terbatasRekening' => false,
        'bisaDibatasiRekening' => false,
      ]);
    }

    $kolomUrut = ['nama' => 'nama_barang', 'harga' => 'harga_barang'];
    $urutReq = $request->input('urut');
    $urutKunci = isset($kolomUrut[$urutReq]) ? $urutReq : 'nama';
    $arah = $this->arah($request);

    $daftarTahun = BosRefAcuanBarang::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan rekening (Fase 1.11): secara bawaan
    // hanya barang pada rekening yang pernah dipakai
    // kertas kerja sekolah ini yang tampil — katalog
    // SSH nasional memuat barang semua sektor (sapi,
    // perahu, bioflok) yang tidak pernah bisa
    // dibelanjakan sekolah. ?semua=1 menonaktifkannya.
    $rekeningTerpakai = $this->rekeningTerpakai($sekolahId);
    $terbatasRekening = $rekeningTerpakai !== [] && $request->input('semua') !== '1';

    $baris = BosRefAcuanBarang::query()
      ->when($terbatasRekening, fn($x) => $x->whereIn('kode_rekening', $rekeningTerpakai))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
      ->when($request->filled('blok'), fn($x) => $x->where('blok_id', 'like', $request->input('blok') . '%'))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('nama_barang', 'like', $cari)
            ->orWhere('id_barang', 'like', $cari)
            ->orWhere('kode_rekening', 'like', $cari);
        });
      })
      ->orderBy($kolomUrut[$urutKunci], $arah)
      ->orderBy('nama_barang')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-barang', $konteks + [
      'baris' => $baris,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'daftarBlok' => BosRefAcuanBarang::query()
        ->distinct()->orderBy('blok_id')->pluck('blok_id')->all(),
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => $this->petaAlias($sekolahId, $baris->getCollection()->pluck('nama_barang')),
      'bisaTulisAlias' => $this->bisaTulisAlias(),
      'terbatasRekening' => $terbatasRekening,
      'bisaDibatasiRekening' => $rekeningTerpakai !== [],
    ]);
  }

  // ── Referensi: Kode Kegiatan ─────────────────────────────

  public function referensiKegiatan(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-kegiatan', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
        'tahunTerpilih' => null,
        'bentukSekolah' => null,
        'terbatasJenjang' => false,
        'terbatasLingkup' => false,
        'bisaDibatasi' => false,
      ]);
    }

    $daftarTahun = BosRefKode::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan jenjang (Fase 1.10) + sumber dana
    // (Fase 1.11): secara bawaan hanya varian bentuk
    // pendidikan sekolah ini DAN sumber dana yang
    // dimiliki sekolah ini (dari anggarannya) yang
    // tampil — kode milik jenjang lain, program Banpem
    // khusus, atau sumber dana yang tidak dimiliki
    // sekolah tidak lagi mengotori daftar. ?semua=1
    // menonaktifkan keduanya (?jenjang=semua tetap
    // diterima sebagai aliasnya). Cermin sendiri tetap
    // lengkap; ini murni saringan tampil.
    $bentuk = $this->bentukSekolah($sekolahId);
    $sumberSet = $this->sumberSekolah($sekolahId, $tahunEfektif);
    $tampilkanSemua = $request->input('semua') === '1' || $request->input('jenjang') === 'semua';
    $terbatasJenjang = $bentuk !== null && !$tampilkanSemua;
    $terbatasSumber = $sumberSet !== [] && !$tampilkanSemua;

    // ref_kode tersimpan per varian (sumber dana/bentuk);
    // untuk referensi cukup tampilkan baris unik per
    // (id_kode, uraian, level, tahun). Saringan bentuk
    // & sumber diterapkan SEBELUM pengelompokan agar
    // kelompok menyusut ke varian sekolah ini.
    $baris = BosRefKode::query()
      ->whereNotNull('id_kode')
      ->when($terbatasJenjang, fn($x) => $x->where('bentuk_pendidikan_id', $bentuk))
      ->when($terbatasSumber, fn($x) => $x->whereIn('sumber_dana_id', $sumberSet))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('uraian_kode', 'like', $cari)
            ->orWhere('id_kode', 'like', $cari);
        });
      })
      ->select('id_kode', 'uraian_kode', 'id_level_kode', 'tahun')
      ->groupBy('id_kode', 'uraian_kode', 'id_level_kode', 'tahun')
      ->orderBy('tahun')
      ->orderBy('id_kode')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-kegiatan', $konteks + [
      'baris' => $baris,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'bentukSekolah' => $bentuk,
      'terbatasJenjang' => $terbatasJenjang,
      'terbatasLingkup' => $terbatasJenjang || $terbatasSumber,
      'bisaDibatasi' => $bentuk !== null || $sumberSet !== [],
    ]);
  }

  // ── Referensi: Kode Rekening ─────────────────────────────

  public function referensiRekening(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-rekening', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
        'tahunTerpilih' => null,
        'terbatasRekening' => false,
        'bisaDibatasiRekening' => false,
      ]);
    }

    $daftarTahun = BosRefRekening::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan rekening (Fase 1.11): sama seperti
    // Referensi Barang — secara bawaan hanya rekening
    // yang pernah dipakai kertas kerja sekolah ini.
    // ?semua=1 menonaktifkannya.
    $rekeningTerpakai = $this->rekeningTerpakai($sekolahId);
    $terbatasRekening = $rekeningTerpakai !== [] && $request->input('semua') !== '1';

    $baris = BosRefRekening::query()
      ->when($terbatasRekening, fn($x) => $x->whereIn('kode_rekening', $rekeningTerpakai))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('kode_rekening', 'like', $cari)
            ->orWhere('nama_rekening', 'like', $cari);
        });
      })
      ->orderBy('kode_rekening')
      ->orderBy('tahun')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-rekening', $konteks + [
      'baris' => $baris,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'terbatasRekening' => $terbatasRekening,
      'bisaDibatasiRekening' => $rekeningTerpakai !== [],
    ]);
  }

  // ── Kartu dana & anggaran (halaman BKU) ─────────────────

  /**
   * Ringkasan dana dari jumlah saldo per jenis BKU
   * ($perJenis: [id_ref_bku => ['jml' => int, 'total' => float]]) yang
   * sudah dibatasi pada saldo awal terakhir + transaksi sesudahnya.
   * Dana tersedia = tunai + non tunai (bank). Jenis di luar pemetaan
   * tidak ikut dihitung dan dilaporkan lewat 'takDikenal'.
   */
  private function ringkasDana(array $perJenis, float $pajak): array
  {
    $jumlahkan = function (array $peta) use ($perJenis): float {
      $hasil = 0.0;
      foreach ($peta as $idJenis => $tanda) {
        $hasil += $tanda * ($perJenis[$idJenis]['total'] ?? 0.0);
      }
      return $hasil;
    };

    $tunai = $jumlahkan(self::DANA_TUNAI);
    $bank = $jumlahkan(self::DANA_BANK);

    $dikenal = array_merge(array_keys(self::DANA_TUNAI), array_keys(self::DANA_BANK), self::DANA_DIABAIKAN);
    $takDikenal = ['jml' => 0, 'ids' => []];
    foreach ($perJenis as $idJenis => $d) {
      if (!in_array($idJenis, $dikenal, true) && $d['jml'] > 0) {
        $takDikenal['jml'] += $d['jml'];
        $takDikenal['ids'][] = $idJenis;
      }
    }

    return [
      'tersedia' => $tunai + $bank,
      'tunai' => $tunai,
      'bank' => $bank,
      'pajak' => $pajak,
      'takDikenal' => $takDikenal,
    ];
  }

  /**
   * Kartu dana: posisi pada akhir bulan $bulanSampai tahun $tahun (atau
   * akhir tahun bila bulan tidak dipilih). Tidak ikut saringan jenis,
   * rekening, atau pencarian — ini posisi, bukan daftar.
   *
   * Cara hitung: untuk TIAP anggaran (sumber dana punya rantai saldo
   * sendiri), ambil baris Saldo Awal Bank/Tunai terakhir sebelum batas,
   * lalu jumlahkan baris itu dan seluruh transaksi sesudahnya sampai
   * batas. Saldo awal ARKAS adalah saldo penutup bulan sebelumnya, jadi
   * hasilnya sama dengan saldo penutup bulan yang bersangkutan.
   *
   * Pajak: jumlah Pajak Belanja Terima sejak 1 Januari sampai batas.
   */
  private function hitungKartuDana(int $sekolahId, ?int $tahun, ?int $bulanSampai): array
  {
    $bulanSampai = ($bulanSampai !== null && $bulanSampai >= 1 && $bulanSampai <= 12) ? $bulanSampai : null;
    $perJenis = [];
    $pajak = 0.0;

    if ($tahun !== null) {
      $awalTahun = \Carbon\Carbon::create($tahun, 1, 1)->startOfDay();
      $sebelum = \Carbon\Carbon::create($tahun, $bulanSampai ?? 12, 1)->addMonth()->startOfMonth();
      $batas = $sebelum->format('Y-m-d H:i:s');

      $sql = <<<SQL
        SELECT k.id_ref_bku, COUNT(*) AS jml, SUM(k.saldo) AS total
        FROM bos_kas_umum k
        WHERE k.sekolah_id = ?
          AND k.soft_delete = 0
          AND k.tanggal_transaksi < ?
          AND k.tanggal_transaksi >= COALESCE((
            SELECT MAX(s.tanggal_transaksi)
            FROM bos_kas_umum s
            WHERE s.sekolah_id = k.sekolah_id
              AND s.soft_delete = 0
              AND s.id_ref_bku IN (8, 9)
              AND s.id_anggaran_arkas = k.id_anggaran_arkas
              AND s.tanggal_transaksi < ?
          ), '1900-01-01 00:00:00')
        GROUP BY k.id_ref_bku
        SQL;

      foreach (\Illuminate\Support\Facades\DB::select($sql, [$sekolahId, $batas, $batas]) as $b) {
        $perJenis[(int) $b->id_ref_bku] = ['jml' => (int) $b->jml, 'total' => (float) $b->total];
      }

      $pajak = (float) BosKasUmum::query()
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_ref_bku', self::JENIS_PAJAK)
        ->where('tanggal_transaksi', '>=', $awalTahun)
        ->where('tanggal_transaksi', '<', $sebelum)
        ->sum('saldo');
    }

    return ['tahun' => $tahun, 'bulan' => $bulanSampai] + $this->ringkasDana($perJenis, $pajak);
  }

  /**
   * Kartu anggaran s.d. bulan berjalan — definisi yang sama dengan
   * Kertas Kerja (titik potong bulan otomatis, versi RKAS disetujui
   * terakhir), tetapi mencakup semua sumber dana karena halaman BKU
   * tidak per sumber dana.
   *  - bisa      : jumlah sisa jadwal baris yang masih bersisa
   *                (kartu "Bisa Dibelanjakan" Kertas Kerja)
   *  - sudah     : realisasi BKU belanja baris-baris itu
   *  - bulanIni  : jadwal bulan titik potong saja
   *  - terjadwal : jadwal kumulatif Januari s.d. bulan titik potong
   */
  private function hitungKartuAnggaran(int $sekolahId, ?int $tahun): array
  {
    $tahunIni = (int) now()->format('Y');
    $tahunAcuan = $tahun ?? $tahunIni;
    $bulanPotong = $tahunAcuan === $tahunIni
      ? (int) now()->format('n')
      : ($tahunAcuan < $tahunIni ? 12 : 0);

    $hasil = [
      'tahun' => $tahunAcuan,
      'bulan' => $bulanPotong,
      'ada' => false,
      'terjadwal' => 0.0,
      'bisa' => 0.0,
      'sudah' => 0.0,
      'bulanIni' => 0.0,
    ];

    if ($bulanPotong < 1) {
      return $hasil;
    }

    $ids = $this->pilihVersiIds($this->kelompokVersi($sekolahId), $tahunAcuan, null, null);
    if (!$ids) {
      return $hasil;
    }

    $periodeIni = 80 + $bulanPotong; // id_periode 81–92 = Januari–Desember
    $jenisBelanja = implode(',', array_map('intval', self::JENIS_BELANJA));
    $tandaTanya = implode(',', array_fill(0, count($ids), '?'));

    $sql = <<<SQL
      SELECT
        COALESCE(SUM(jad.jadwal), 0) AS terjadwal,
        COALESCE(SUM(jad.bulan_ini), 0) AS bulan_ini,
        COALESCE(SUM(rea.realisasi), 0) AS sudah,
        COALESCE(SUM(CASE
          WHEN COALESCE(jad.jadwal, 0) > 0 AND COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) > 0
          THEN COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0)
          ELSE 0 END), 0) AS bisa
      FROM bos_rapbs
      LEFT JOIN (
        SELECT sekolah_id, id_rapbs_arkas,
               SUM(jumlah) AS jadwal,
               SUM(CASE WHEN id_periode = {$periodeIni} THEN jumlah ELSE 0 END) AS bulan_ini
        FROM bos_rapbs_periode
        WHERE soft_delete = 0 AND id_periode BETWEEN 81 AND {$periodeIni}
        GROUP BY sekolah_id, id_rapbs_arkas
      ) jad ON jad.id_rapbs_arkas = bos_rapbs.id_arkas AND jad.sekolah_id = bos_rapbs.sekolah_id
      LEFT JOIN (
        SELECT per16.sekolah_id, per16.id_rapbs_arkas, SUM(bku16.saldo) AS realisasi
        FROM bos_kas_umum bku16
        JOIN bos_rapbs_periode per16
          ON per16.id_arkas = bku16.id_rapbs_periode AND per16.sekolah_id = bku16.sekolah_id
        WHERE bku16.soft_delete = 0
          AND bku16.id_ref_bku IN ({$jenisBelanja})
          AND bku16.id_rapbs_periode IS NOT NULL
        GROUP BY per16.sekolah_id, per16.id_rapbs_arkas
      ) rea ON rea.id_rapbs_arkas = bos_rapbs.id_arkas AND rea.sekolah_id = bos_rapbs.sekolah_id
      WHERE bos_rapbs.sekolah_id = ?
        AND bos_rapbs.soft_delete = 0
        AND bos_rapbs.id_anggaran_arkas IN ({$tandaTanya})
      SQL;

    $agg = \Illuminate\Support\Facades\DB::selectOne($sql, array_merge([$sekolahId], $ids));

    return [
      'ada' => true,
      'terjadwal' => (float) ($agg->terjadwal ?? 0),
      'bisa' => (float) ($agg->bisa ?? 0),
      'sudah' => (float) ($agg->sudah ?? 0),
      'bulanIni' => (float) ($agg->bulan_ini ?? 0),
    ] + $hasil;
  }

  // ── BKU ──────────────────────────────────────────────────

  public function bku(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.bku', $konteks + [
        'baris' => null,
        'totalNominal' => 0,
        'daftarTahun' => [],
        'daftarJenis' => collect(),
        'daftarRekening' => collect(),
        'urut' => 'tanggal',
        'arah' => 'desc',
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'tahunTerpilih' => null,
      ]);
    }

    $dasar = fn() => BosKasUmum::query()
      ->from('bos_kas_umum')
      ->leftJoin('bos_ref_bku', function ($j) {
        $j->on('bos_ref_bku.id_ref_bku', '=', 'bos_kas_umum.id_ref_bku');
      })
      ->leftJoin('bos_kas_nota', function ($j) {
        $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
          ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->where('bos_kas_umum.sekolah_id', $sekolahId)
      ->where('bos_kas_umum.soft_delete', false);

    // Bawaan tahun (Fase 1.10): tahun berjalan bila ada
    // transaksi tahun itu, selain itu tahun terbesar.
    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
    $daftarTahun = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->selectRaw(($driver === 'mysql'
        ? 'YEAR(tanggal_transaksi)'
        : "CAST(strftime('%Y', tanggal_transaksi) AS INTEGER)") . ' as tahun')
      ->distinct()
      ->orderByDesc('tahun')
      ->pluck('tahun')
      ->map(fn($t) => (int) $t)
      ->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    $saring = function ($q) use ($request, $tahunEfektif) {
      $q->when($tahunEfektif !== null, fn($x) => $x->whereYear('bos_kas_umum.tanggal_transaksi', $tahunEfektif))
        ->when($request->filled('bulan'), fn($x) => $x->whereMonth('bos_kas_umum.tanggal_transaksi', $request->integer('bulan')))
        ->when($request->filled('jenis'), fn($x) => $x->where('bos_kas_umum.id_ref_bku', $request->integer('jenis')))
        ->when($request->filled('rekening'), fn($x) => $x->where('bos_kas_umum.kode_rekening', $request->input('rekening')))
        ->when($request->filled('cari'), function ($x) use ($request) {
          $cari = '%' . $request->input('cari') . '%';
          $x->where(function ($y) use ($cari) {
            $y->where('bos_kas_umum.uraian', 'like', $cari)
              ->orWhere('bos_kas_umum.no_bukti', 'like', $cari)
              ->orWhere('bos_kas_nota.nama_toko', 'like', $cari);
          });
        });
      return $q;
    };

    $totalNominal = (float) $saring($dasar())->sum('bos_kas_umum.saldo');

    // Kartu dana & anggaran di atas tabel (tidak ikut saringan
    // jenis/rekening/pencarian). Saat "Semua tahun" dipilih, kartu dana
    // memakai tahun terbaru karena saldo awal dicatat per tahun.
    $tahunDana = $tahunEfektif ?? ($daftarTahun[0] ?? null);
    $kartuDana = $this->hitungKartuDana(
      $sekolahId,
      $tahunDana,
      $request->filled('bulan') ? $request->integer('bulan') : null
    );
    $kartuAnggaran = $this->hitungKartuAnggaran($sekolahId, $tahunEfektif);

    $kolomUrut = [
      'tanggal' => 'bos_kas_umum.tanggal_transaksi',
      'bukti' => 'bos_kas_umum.no_bukti',
      'uraian' => 'bos_kas_umum.uraian',
      'rekening' => 'bos_kas_umum.kode_rekening',
      'nominal' => 'bos_kas_umum.saldo',
    ];
    $urut = $kolomUrut[$request->input('urut')] ?? null;
    $urutKunci = $urut ? $request->input('urut') : 'tanggal';
    $arah = $this->arah($request);

    // Nama rekening dipilih per tahun transaksi lewat subquery
    // (satu kode rekening punya nama per tahun di ref ARKAS).
    $tahunTransaksi = $driver === 'mysql'
      ? 'YEAR(bos_kas_umum.tanggal_transaksi)'
      : "CAST(strftime('%Y', bos_kas_umum.tanggal_transaksi) AS INTEGER)";

    $baris = $saring($dasar())
      ->select([
        'bos_kas_umum.*',
        'bos_ref_bku.nama_bku',
        'bos_ref_bku.kode_bku',
        'bos_kas_nota.nama_toko',
      ])
      ->selectRaw("(SELECT nama_rekening FROM bos_ref_rekening r
        WHERE r.kode_rekening = bos_kas_umum.kode_rekening
          AND r.tahun = {$tahunTransaksi}
        LIMIT 1) as nama_rekening")
      ->orderBy($urut ?? 'bos_kas_umum.tanggal_transaksi', $urut ? $arah : 'desc')
      ->orderByDesc('bos_kas_umum.id')
      ->paginate(25)
      ->withQueryString();

    $daftarJenis = BosRefBku::query()
      ->whereIn('id_ref_bku', function ($q) use ($sekolahId) {
        $q->select('id_ref_bku')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->orderBy('nama_bku')
      ->get();

    $daftarRekening = BosRefRekening::query()
      ->whereIn('kode_rekening', function ($q) use ($sekolahId) {
        $q->select('kode_rekening')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)
          ->whereNotNull('kode_rekening')->distinct();
      })
      ->select('kode_rekening', 'nama_rekening')
      ->distinct()
      ->orderBy('kode_rekening')
      ->get();

    return view('content.kelola-bos.bku', $konteks + [
      'baris' => $baris,
      'totalNominal' => $totalNominal,
      'kartuDana' => $kartuDana,
      'kartuAnggaran' => $kartuAnggaran,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'daftarJenis' => $daftarJenis,
      'daftarRekening' => $daftarRekening,
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => $this->petaAlias($sekolahId, $baris->getCollection()->pluck('uraian')),
      'bisaTulisAlias' => $this->bisaTulisAlias(),
    ]);
  }

  // ── SPJ Kwitansi (Fase 1) ─────────────────────────────
  //
  // Kwitansi SPJ dibuat dari baris belanja BKU, digabung
  // per nota / nomor bukti (disetujui user 2026-11-26):
  // satu nota ARKAS adalah satu pembayaran ke satu toko,
  // jadi satu kwitansi per nota — baris-baris barangnya
  // menjadi rincian di badan kwitansi. Nomor urut
  // kwitansi adalah posisi grup dalam tahun transaksinya
  // (deterministik, tanpa tabel penyimpanan).

  /** Memo penandatangan per sekolah selama satu request. */
  private array $memoPenandatangan = [];

  /** Kunci pengelompokan kwitansi untuk satu baris BKU. */
  private function kunciGrupKwitansi(BosKasUmum $b): string
  {
    if (!empty($b->id_kas_nota)) {
      return 'nota:' . $b->id_kas_nota;
    }

    $noBukti = trim((string) ($b->no_bukti ?? ''));
    if ($noBukti !== '') {
      return 'bukti:' . $noBukti . '|' . ($b->tanggal_transaksi?->format('Y-m-d') ?? '');
    }

    return 'baris:' . $b->id;
  }

  /**
   * Kelompokkan baris-baris menjadi grup kwitansi. Hanya
   * baris belanja (JENIS_BELANJA) yang belum terhapus.
   *
   * @return array<string, BosKasUmum[]>
   */
  private function grupKwitansi($baris): array
  {
    $grup = [];
    foreach ($baris as $b) {
      if ($b->soft_delete) {
        continue;
      }
      if (!in_array((int) $b->id_ref_bku, self::JENIS_BELANJA, true)) {
        continue;
      }
      $grup[$this->kunciGrupKwitansi($b)][] = $b;
    }

    return $grup;
  }

  /** Urutkan grup menurut tanggal transaksi paling awal grup, lalu id baris terkecil grup. */
  private function urutkanGrupKwitansi(array $grup): array
  {
    $meta = fn(array $rows) => [
      collect($rows)->map(fn($r) => $r->tanggal_transaksi?->getTimestamp() ?? 0)->min(),
      collect($rows)->min('id'),
    ];
    uasort($grup, fn($a, $b) => $meta($a) <=> $meta($b));

    return $grup;
  }

  /** Posisi grup (1-based) di antara semua grup belanja sekolah pada tahun itu. */
  private function nomorUrutKwitansi(int $sekolahId, string $kunciGrup, int $tahun): int
  {
    $baris = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->whereYear('tanggal_transaksi', $tahun)
      ->orderBy('tanggal_transaksi')
      ->orderBy('id')
      ->get();

    $grup = $this->urutkanGrupKwitansi($this->grupKwitansi($baris));
    $posisi = array_search($kunciGrup, array_keys($grup), true);

    return $posisi === false ? 0 : $posisi + 1;
  }

  /**
   * Penandatangan kwitansi: Kepala Sekolah dari detail
   * sekolah (cadangan PTK ber-jabatan Kepala Sekolah) dan
   * Bendahara dari PTK (jabatan atau peran tambahan).
   *
   * @return array{kepala: array{nama: ?string, nip: ?string}, bendahara: array{nama: ?string, nip: ?string}}
   */
  private function penandatangan(int $sekolahId, $sekolah): array
  {
    if (isset($this->memoPenandatangan[$sekolahId])) {
      return $this->memoPenandatangan[$sekolahId];
    }

    $kepala = ['nama' => null, 'nip' => null];
    $detail = $sekolah?->detailTerbaru;
    if ($detail && trim((string) $detail->kepala_sekolah) !== '') {
      $kepala = ['nama' => $detail->kepala_sekolah, 'nip' => $detail->nip];
    } else {
      $ptkKepala = Ptk::where('sekolah_id', $sekolahId)
        ->where('jabatan', 'Kepala Sekolah')
        ->where('status', 'aktif')
        ->first();
      if ($ptkKepala) {
        $kepala = ['nama' => $ptkKepala->nama_lengkap, 'nip' => $ptkKepala->nip];
      }
    }

    $bendahara = ['nama' => null, 'nip' => null];
    $ptkBendahara = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->where(function ($q) {
        $q->where('jabatan', 'Bendahara')
          ->orWhereJsonContains('peran_tambahan', 'Bendahara');
      })
      ->first();
    if ($ptkBendahara) {
      $bendahara = ['nama' => $ptkBendahara->nama_lengkap, 'nip' => $ptkBendahara->nip];
    }

    return $this->memoPenandatangan[$sekolahId] = [
      'kepala' => $kepala,
      'bendahara' => $bendahara,
    ];
  }

  /** Susun data satu kwitansi dari grup barisnya. */
  private function dataKwitansi(int $sekolahId, array $grup, int $nomorUrut): array
  {
    $grup = array_values($grup);
    $pertama = $grup[0];

    $nota = null;
    if (!empty($pertama->id_kas_nota)) {
      $nota = BosKasNota::where('id_arkas', $pertama->id_kas_nota)
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->first();
    }

    $total = array_sum(array_map(fn($b) => (float) $b->saldo, $grup));

    $rekeningUnik = collect($grup)->pluck('kode_rekening')->filter()->unique()->values();
    $kodeRekening = $rekeningUnik->count() === 1
      ? $rekeningUnik->first()
      : ($nota->kode_rekening ?? null);

    $fmtVolume = function ($v) {
      if ($v === null) {
        return null;
      }
      $s = number_format((float) $v, 2, ',', '.');
      return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
    };

    $tanggal = $nota->tanggal_nota ?? $pertama->tanggal_transaksi;
    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);

    return [
      'nomor' => str_pad((string) $nomorUrut, 3, '0', STR_PAD_LEFT),
      'no_bukti' => $nota->no_bukti ?? ($pertama->no_bukti ?? null),
      'kode_rekening' => $kodeRekening,
      'uraian' => count($grup) === 1
        ? $pertama->uraian
        : 'Pembayaran belanja barang/jasa dengan rincian sebagai berikut',
      'rincian' => array_map(fn($b) => [
        'uraian' => $b->uraian,
        'kode_rekening' => $b->kode_rekening,
        'volume' => $fmtVolume($b->volume),
        'saldo' => (float) $b->saldo,
      ], $grup),
      'total' => $total,
      'terbilang' => Terbilang::rupiah($total),
      'tanggal' => $tanggal?->format('d-m-Y'),
      'penerima' => $nota->nama_toko ?? null,
      'ttd' => $this->penandatangan($sekolahId, $sekolah),
    ];
  }

  /** Cetak kwitansi untuk grup nota/bukti dari satu baris BKU. */
  public function cetakKwitansi(Request $request, int $id)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];
    if (!$sekolahId) {
      abort(404);
    }

    $baris = BosKasUmum::where('id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->first();
    if (!$baris) {
      abort(404);
    }

    $kunci = $this->kunciGrupKwitansi($baris);

    $q = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA);
    if (str_starts_with($kunci, 'nota:')) {
      $q->where('id_kas_nota', $baris->id_kas_nota);
    } elseif (str_starts_with($kunci, 'bukti:')) {
      $q->whereNull('id_kas_nota')
        ->where('no_bukti', $baris->no_bukti)
        ->whereDate('tanggal_transaksi', $baris->tanggal_transaksi);
    } else {
      $q->where('id', $baris->id);
    }
    $grup = $q->orderBy('tanggal_transaksi')->orderBy('id')->get()->all();

    $tahun = (int) ($baris->tanggal_transaksi?->format('Y') ?? now()->format('Y'));
    $nomor = $this->nomorUrutKwitansi($sekolahId, $kunci, $tahun);
    $data = $this->dataKwitansi($sekolahId, $grup, $nomor);

    return Pdf::loadView('content.kelola-bos.kwitansi-pdf', [
      'daftarKwitansi' => [$data],
      'sekolah' => $konteks['sekolah'] ?? Sekolah::with('detailTerbaru')->find($sekolahId),
    ])->setPaper('a4', 'portrait')
      ->stream('kwitansi-' . ($data['no_bukti'] ?? $id) . '.pdf');
  }

  /** Cetak kwitansi semua transaksi belanja sesuai saringan halaman BKU. */
  public function cetakKwitansiMassal(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];
    if (!$sekolahId) {
      abort(404);
    }

    // Saringan sama seperti bku(), selalu dibatasi jenis belanja.
    $dasar = fn() => BosKasUmum::query()
      ->from('bos_kas_umum')
      ->leftJoin('bos_ref_bku', function ($j) {
        $j->on('bos_ref_bku.id_ref_bku', '=', 'bos_kas_umum.id_ref_bku');
      })
      ->leftJoin('bos_kas_nota', function ($j) {
        $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
          ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->where('bos_kas_umum.sekolah_id', $sekolahId)
      ->where('bos_kas_umum.soft_delete', false);

    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
    $daftarTahun = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->selectRaw(($driver === 'mysql'
        ? 'YEAR(tanggal_transaksi)'
        : "CAST(strftime('%Y', tanggal_transaksi) AS INTEGER)") . ' as tahun')
      ->distinct()
      ->orderByDesc('tahun')
      ->pluck('tahun')
      ->map(fn($t) => (int) $t)
      ->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    $saring = function ($q) use ($request, $tahunEfektif) {
      $q->when($tahunEfektif !== null, fn($x) => $x->whereYear('bos_kas_umum.tanggal_transaksi', $tahunEfektif))
        ->when($request->filled('bulan'), fn($x) => $x->whereMonth('bos_kas_umum.tanggal_transaksi', $request->integer('bulan')))
        ->when($request->filled('jenis'), fn($x) => $x->where('bos_kas_umum.id_ref_bku', $request->integer('jenis')))
        ->when($request->filled('rekening'), fn($x) => $x->where('bos_kas_umum.kode_rekening', $request->input('rekening')))
        ->when($request->filled('cari'), function ($x) use ($request) {
          $cari = '%' . $request->input('cari') . '%';
          $x->where(function ($y) use ($cari) {
            $y->where('bos_kas_umum.uraian', 'like', $cari)
              ->orWhere('bos_kas_umum.no_bukti', 'like', $cari)
              ->orWhere('bos_kas_nota.nama_toko', 'like', $cari);
          });
        });
      return $q;
    };

    $baris = $saring($dasar())
      ->whereIn('bos_kas_umum.id_ref_bku', self::JENIS_BELANJA)
      ->select('bos_kas_umum.*')
      ->orderBy('bos_kas_umum.tanggal_transaksi')
      ->orderBy('bos_kas_umum.id')
      ->get();

    $grup = $this->urutkanGrupKwitansi($this->grupKwitansi($baris));

    if (!$grup) {
      return redirect()->route('kelola-bos.bku', $request->query())
        ->with('error', 'Tidak ada transaksi belanja pada saringan ini.');
    }

    $daftarKwitansi = [];
    foreach ($grup as $kunci => $rows) {
      $tahunGrup = (int) (collect($rows)->map(fn($r) => $r->tanggal_transaksi?->format('Y'))->filter()->min()
        ?? now()->format('Y'));
      $nomor = $this->nomorUrutKwitansi($sekolahId, (string) $kunci, $tahunGrup);
      $daftarKwitansi[] = $this->dataKwitansi($sekolahId, $rows, $nomor);
    }

    return Pdf::loadView('content.kelola-bos.kwitansi-pdf', [
      'daftarKwitansi' => $daftarKwitansi,
      'sekolah' => $konteks['sekolah'] ?? Sekolah::with('detailTerbaru')->find($sekolahId),
    ])->setPaper('a4', 'portrait')
      ->stream('kwitansi-massal.pdf');
  }

  // ── Token sinkron (Fase 1.12, lapisan kerja) ──────────
  //
  // Token adalah kunci agen sinkron di laptop ARKAS.
  // Yang disimpan di database hanya hash sha256-nya;
  // token asli ditampilkan SEKALI sesudah dibuat (lewat
  // flash session) dan tidak bisa dilihat lagi — lupa
  // berarti buat ulang, dan pembuatan ulang mencabut
  // token lama seketika. Pembuatan di sini persis
  // meniru perintah `bos:token` (Str::random(48) +
  // updateOrCreate hash + terakhir_dipakai direset).

  /** Halaman status token agen sinkron sekolah ini. */
  public function token(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    $tokenBaris = $sekolahId
      ? BosSinkronToken::where('sekolah_id', $sekolahId)->first()
      : null;

    return view('content.kelola-bos.token', $konteks + [
      'tokenBaris' => $tokenBaris,
    ]);
  }

  /**
   * Buat / buat ulang token. Token asli hanya hidup di
   * flash session satu kali tampil; tidak pernah
   * ditulis ke database, log, atau tempat lain.
   */
  public function tokenBuat(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Konteks sekolah belum dipilih — token tidak dibuat.');
    }

    $token = Str::random(48);

    BosSinkronToken::updateOrCreate(
      ['sekolah_id' => $sekolahId],
      ['token_hash' => hash('sha256', $token), 'terakhir_dipakai' => null]
    );

    return redirect()->route('kelola-bos.token')
      ->with('token_baru', $token)
      ->with('success', 'Token sinkron baru dibuat untuk sekolah ini. Salin dan simpan sekarang — token tidak akan ditampilkan lagi.');
  }
}
