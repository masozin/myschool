<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\JadwalPengaturan;
use App\Models\KalenderPendidikan;
use App\Models\Ptk;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\SekolahLogo;
use App\Models\Semester;
use App\Models\WebBerita;
use App\Models\WebDokumen;
use App\Models\WebGaleri;
use App\Models\WebGaleriKegiatan;
use App\Models\WebSekolah;
use Illuminate\Support\Facades\Storage;

class WebSekolahController extends Controller
{
  /*
  |--------------------------------------------------------------------------
  | Halaman publik Web Sekolah — TANPA auth.
  | Sebuah sekolah tampil hanya bila: ada baris tabel_web_sekolah,
  | is_aktif = true, DAN fitur 'web-sekolah' sekolah itu aktif
  | (gerbang fitur diperiksa manual di sini karena tamu tidak login,
  | jadi middleware fitur: yang berbasis user tidak bisa dipakai).
  |--------------------------------------------------------------------------
  */

  // ── Root `/` ────────────────────────────────────────────────────────
  // Keputusan Mark 2026-11-21: root TIDAK menampilkan landing page —
  // selalu redirect ke home (login/dashboard), seperti perilaku lama.
  // Landing page sekolah dibuka lewat slug-nya saja: /sekolah/{slug}.
  // (View direktori + daftarWebPublik() sengaja dipertahankan agar
  // perilaku lama mudah dikembalikan bila berubah pikiran.)
  public function beranda()
  {
    return redirect()->route('home');
  }

  // ── Landing page satu sekolah ───────────────────────────────────────
  public function tampil(string $slugWeb)
  {
    $web = $this->cariWebPublik($slugWeb);
    $data = $this->dataSekolah($web);

    $data['beritaTerbaru'] = $web->sekolah->webBerita()
      ->terbit()
      ->latest('diterbitkan_pada')
      ->take(6)
      ->get();

    // Galeri tampil berkelompok: kartu album kegiatan (dengan foto
    // sampul + jumlah foto) lebih dulu, lalu foto tanpa album.
    $data['albumGaleri'] = $web->sekolah->webGaleriKegiatan()
      ->withCount('galeri')
      ->with(['galeri' => fn ($q) => $q->orderBy('urutan')->latest('id')])
      ->orderByRaw('tanggal IS NULL')
      ->orderByDesc('tanggal')
      ->latest('id')
      ->get()
      ->filter(fn ($kegiatan) => $kegiatan->galeri_count > 0)
      ->values();

    $data['galeri'] = $web->sekolah->webGaleri()
      ->whereNull('kegiatan_id')
      ->orderBy('urutan')
      ->latest('id')
      ->take(8)
      ->get();

    // Agenda terdekat dari kalender pendidikan (kaldik bersifat global,
    // berlaku untuk semua sekolah di instalasi ini). Yang sedang berjalan
    // ikut tampil: batasnya tanggal_selesai, bukan tanggal_mulai.
    $data['agenda'] = KalenderPendidikan::query()
      ->whereDate('tanggal_selesai', '>=', now()->toDateString())
      ->orderBy('tanggal_mulai')
      ->take(5)
      ->get();

    // Dewan guru & tendik aktif sekolah ini. Kepala Sekolah paling depan,
    // lalu Guru, lalu tendik lain; nama adalah data publik sekolah.
    $data['dewanGuru'] = $web->sekolah->ptk()
      ->where('status', 'aktif')
      ->orderByRaw("CASE WHEN jabatan = 'Kepala Sekolah' THEN 0 WHEN jabatan = 'Guru' THEN 1 ELSE 2 END")
      ->orderBy('nama_ptk')
      ->take(12)
      ->get();

    // Jadwal pelajaran HARI INI per rombel untuk seksi publik di landing.
    $data['jadwalHariIni'] = $this->jadwalHariIni($web->sekolah, $data['semesterAktif']);

    // Dokumen publik (akreditasi, KOSP, RKT/RKJM, dst.) yang ditandai
    // tampil, dikelompokkan per kategori mengikuti urutan WebDokumen::KATEGORI.
    $urutanKategori = array_flip(array_keys(WebDokumen::KATEGORI));
    $data['dokumenPublik'] = $web->sekolah->webDokumen()
      ->where('is_tampil', true)
      ->get()
      ->sortBy(fn($d) => [
        $urutanKategori[$d->kategori] ?? 99,
        -($d->tahun ?? 0),
        strtolower($d->judul),
      ])
      ->groupBy('kategori');

    return view('publik.web-sekolah.tampil', $data);
  }

  // ── Daftar semua berita terbit satu sekolah ─────────────────────────
  public function beritaIndex(string $slugWeb)
  {
    $web = $this->cariWebPublik($slugWeb);
    $data = $this->dataSekolah($web);

    $data['berita'] = $web->sekolah->webBerita()
      ->terbit()
      ->latest('diterbitkan_pada')
      ->paginate(9);

    return view('publik.web-sekolah.berita-index', $data);
  }

  // ── Baca satu berita ────────────────────────────────────────────────
  public function beritaTampil(string $slugWeb, string $slugBerita)
  {
    $web = $this->cariWebPublik($slugWeb);
    $data = $this->dataSekolah($web);

    $data['berita'] = $web->sekolah->webBerita()
      ->terbit()
      ->where('slug', $slugBerita)
      ->firstOrFail();

    $data['beritaLain'] = $web->sekolah->webBerita()
      ->terbit()
      ->where('id', '!=', $data['berita']->id)
      ->latest('diterbitkan_pada')
      ->take(3)
      ->get();

    return view('publik.web-sekolah.berita-tampil', $data);
  }

  // ── Berkas gambar dialirkan langsung dari disk public ───────────────
  // Mengikuti pola SekolahLogoController::lihat — TIDAK memakai
  // asset('storage/...') karena symlink public/storage tidak selalu
  // ada di hosting bersama, gambar akan tampak pecah padahal ada.
  public function fotoHero(string $slugWeb)
  {
    $web = $this->cariWebPublik($slugWeb);
    abort_if(!$web->foto_hero || !Storage::disk('public')->exists($web->foto_hero), 404);

    return Storage::disk('public')->response($web->foto_hero);
  }

  public function logo(string $slugWeb)
  {
    $web = $this->cariWebPublik($slugWeb);
    $logo = $web->sekolah->asets->where('tipe', SekolahLogo::TIPE_LOGO_SEKOLAH)->first();
    abort_if(!$logo || !Storage::disk('public')->exists($logo->path_file), 404);

    return Storage::disk('public')->response($logo->path_file);
  }

  public function fotoBerita(WebBerita $berita)
  {
    abort_if($berita->status !== WebBerita::STATUS_TERBIT, 404);
    abort_if(!$berita->sekolah?->webSekolah?->tampilPublik(), 404);
    abort_if(!$berita->foto || !Storage::disk('public')->exists($berita->foto), 404);

    return Storage::disk('public')->response($berita->foto);
  }

  // ── Halaman publik satu album kegiatan ────────────────────────────
  public function galeriAlbum(string $slugWeb, WebGaleriKegiatan $kegiatan)
  {
    $web = $this->cariWebPublik($slugWeb);
    abort_if($kegiatan->sekolah_id !== $web->sekolah_id, 404);

    $data = $this->dataSekolah($web);
    $data['kegiatan'] = $kegiatan;
    $data['fotoAlbum'] = $kegiatan->galeri()->get();

    return view('publik.web-sekolah.galeri-album', $data);
  }

  public function fotoGaleri(WebGaleri $galeri)
  {
    abort_if(!$galeri->sekolah?->webSekolah?->tampilPublik(), 404);
    abort_if(!Storage::disk('public')->exists($galeri->foto), 404);

    return Storage::disk('public')->response($galeri->foto);
  }

  // Foto PTK untuk seksi Dewan Guru di landing page. Dialirkan lewat route
  // (bukan asset('storage/...')) dengan alasan yang sama seperti foto lain:
  // symlink public/storage tidak selalu ada di hosting bersama.
  public function fotoPtk(Ptk $ptk)
  {
    abort_if(!$ptk->sekolah?->webSekolah?->tampilPublik(), 404);
    abort_if(!$ptk->photo || !Storage::disk('public')->exists($ptk->photo), 404);

    return Storage::disk('public')->response($ptk->photo);
  }

  // ── Dokumen publik: lihat (inline di browser) & unduh ─────────────
  // Hanya dokumen yang ditandai tampil pada web yang tampil publik.
  // Dokumen yang disembunyikan operator TIDAK bisa diakses dari sini.

  public function dokumenLihat(WebDokumen $dokumen)
  {
    abort_if(!$dokumen->is_tampil, 404);
    abort_if(!$dokumen->sekolah?->webSekolah?->tampilPublik(), 404);
    abort_if(!Storage::disk('public')->exists($dokumen->berkas), 404);

    return Storage::disk('public')->response($dokumen->berkas);
  }

  public function dokumenUnduh(WebDokumen $dokumen)
  {
    abort_if(!$dokumen->is_tampil, 404);
    abort_if(!$dokumen->sekolah?->webSekolah?->tampilPublik(), 404);
    abort_if(!Storage::disk('public')->exists($dokumen->berkas), 404);

    return Storage::disk('public')->download(
      $dokumen->berkas,
      $dokumen->nama_berkas ?: basename($dokumen->berkas)
    );
  }

  // ── Jadwal pelajaran hari ini (publik) ─────────────────────────────
  // Kerangka jam + garis waktu di bawah MENYELARASKAN JadwalApiController
  // (pola yang sama juga dipakai JadwalPelajaranController): model pilihan
  // rombel, lalu model pertama, lalu pola bawaan. Blok per rombel:
  // {mulai, selesai, jenis: pelajaran|istirahat|kegiatan, label, sub, aktif}.

  private const HARI_JADWAL = [
    1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
    5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu',
  ];

  private function jadwalHariIni(Sekolah $sekolah, ?Semester $semester): array
  {
    $sekarang = now();
    $hari = (int) $sekarang->dayOfWeekIso; // 1=Senin .. 7=Minggu
    $namaHari = self::HARI_JADWAL[$hari];

    $hasil = [
      'tampil' => false,
      'namaHari' => $namaHari,
      'tanggal' => $sekarang->translatedFormat('l, d F Y'),
      'libur' => false,
      'alasanLibur' => null,
      'daftar' => [],
    ];

    if (!$semester) {
      return $hasil;
    }

    // Libur mingguan sekolah (bawaan: Minggu).
    $liburMingguan = $sekolah->detailTerbaru?->libur_mingguan;
    if (!is_array($liburMingguan) || $liburMingguan === []) {
      $liburMingguan = ['Minggu'];
    }
    if (in_array($namaHari, $liburMingguan, true)) {
      $hasil['libur'] = true;
      $hasil['alasanLibur'] = 'Hari libur mingguan sekolah.';
    }

    // Libur kalender pendidikan menimpa alasan (lebih spesifik).
    $liburKaldik = KalenderPendidikan::query()
      ->where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $sekarang->toDateString())
      ->whereDate('tanggal_selesai', '>=', $sekarang->toDateString())
      ->first();
    if ($liburKaldik) {
      $hasil['libur'] = true;
      $hasil['alasanLibur'] = $liburKaldik->kegiatan;
    }

    if ($hasil['libur']) {
      $hasil['tampil'] = true;
      return $hasil;
    }

    $jamMenit = $sekarang->hour * 60 + $sekarang->minute;

    $rombels = $sekolah->rombel()
      ->with('ptk')
      ->where('semester_id', $semester->id)
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->take(18)
      ->get();

    foreach ($rombels as $rombel) {
      $blok = $this->blokRombelHariIni($sekolah, $semester->id, $rombel, $hari);
      // Sel pelajaran yang masih kosong tidak ditampilkan ke publik.
      $blok = array_values(array_filter(
        $blok,
        fn($b) => $b['jenis'] !== 'pelajaran' || $b['label'] !== '' || $b['sub'] !== ''
      ));
      if ($blok === []) {
        continue;
      }
      foreach ($blok as &$b) {
        $b['aktif'] = $jamMenit >= $this->keMenit($b['mulai']) && $jamMenit < $this->keMenit($b['selesai']);
      }
      unset($b);

      $hasil['daftar'][] = [
        'nama' => $rombel->nama_rombel,
        'wali' => $rombel->ptk?->nama_lengkap,
        'blok' => $blok,
      ];
    }

    $hasil['tampil'] = $hasil['daftar'] !== [];
    return $hasil;
  }

  private function kerangkaBawaanJadwal(Sekolah $sekolah): array
  {
    $libur = $sekolah->detailTerbaru?->libur_mingguan;
    if (!is_array($libur) || $libur === []) {
      $libur = ['Minggu'];
    }

    $jumlahBawaan = [1 => 8, 2 => 8, 3 => 8, 4 => 8, 5 => 6, 6 => 7];
    $kerangka = [];

    foreach (self::HARI_JADWAL as $hari => $nama) {
      if ($hari === 7 || in_array($nama, $libur, true)) {
        continue;
      }
      $jumlah = $jumlahBawaan[$hari];
      $istirahat = [];
      foreach ([3, 6] as $setelah) {
        if ($setelah < $jumlah) {
          $istirahat[] = ['setelah' => $setelah, 'menit' => 15];
        }
      }
      $kerangka[$hari] = [
        'jumlah_jam' => $jumlah,
        'mulai' => '07:00',
        'menit' => 35,
        'istirahat' => $istirahat,
        'kegiatan' => [],
      ];
    }

    return $kerangka;
  }

  private function kerangkaRombelJadwal(Sekolah $sekolah, int $semesterId, Rombel $rombel): array
  {
    $models = JadwalPengaturan::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semesterId)
      ->orderBy('id')
      ->get();

    $model = null;
    if ($rombel->jadwal_pengaturan_id) {
      $model = $models->firstWhere('id', (int) $rombel->jadwal_pengaturan_id);
    }
    $model ??= $models->first();

    if ($model && is_array($model->kerangka) && $model->kerangka !== []) {
      return $model->kerangka;
    }
    return $this->kerangkaBawaanJadwal($sekolah);
  }

  private function garisWaktuJadwal(array $frame): array
  {
    [$jh, $jm] = array_map('intval', explode(':', $frame['mulai'] ?? '07:00'));
    $t = $jh * 60 + $jm;
    $menit = (int) ($frame['menit'] ?? 35);
    $jumlah = (int) ($frame['jumlah_jam'] ?? 0);
    $fmt = fn(int $m) => sprintf('%02d.%02d', intdiv($m, 60), $m % 60);

    $kejadian = [];
    foreach ($frame['istirahat'] ?? [] as $jeda) {
      $kejadian[(int) $jeda['setelah']][] = [
        'jenis' => 'istirahat',
        'nama' => 'Istirahat',
        'menit' => (int) $jeda['menit'],
      ];
    }
    foreach ($frame['kegiatan'] ?? [] as $keg) {
      $anchor = ($keg['posisi'] ?? 'sebelum') === 'sesudah'
        ? (int) $keg['jam']
        : (int) $keg['jam'] - 1;
      $kejadian[$anchor][] = [
        'jenis' => 'kegiatan',
        'nama' => $keg['nama'],
        'menit' => (int) $keg['menit'],
      ];
    }

    $pelajaran = [];
    $lainnya = [];
    for ($anchor = 0; $anchor <= $jumlah; $anchor++) {
      foreach ($kejadian[$anchor] ?? [] as $ev) {
        $lainnya[$anchor][] = [
          'nama' => $ev['nama'],
          'jenis' => $ev['jenis'],
          'menit' => $ev['menit'],
          'waktu' => [$fmt($t), $fmt($t + $ev['menit'])],
        ];
        $t += $ev['menit'];
      }
      if ($anchor < $jumlah) {
        $jam = $anchor + 1;
        $pelajaran[$jam] = [$fmt($t), $fmt($t + $menit)];
        $t += $menit;
      }
    }

    return ['pelajaran' => $pelajaran, 'lainnya' => $lainnya];
  }

  private function blokRombelHariIni(Sekolah $sekolah, int $semesterId, Rombel $rombel, int $hari): array
  {
    $frame = $this->kerangkaRombelJadwal($sekolah, $semesterId, $rombel)[$hari] ?? null;
    if (!$frame) {
      return [];
    }
    $gw = $this->garisWaktuJadwal($frame);

    $sel = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->where('hari', $hari)
      ->get()
      ->keyBy('jam_ke');

    $blok = [];
    foreach ($gw['pelajaran'] as $jam => $w) {
      $c = $sel->get($jam);
      $blok[] = [
        'mulai' => $w[0],
        'selesai' => $w[1],
        'jenis' => 'pelajaran',
        'label' => $c?->pengampu
          ? ($c->pengampu->mapping?->masterMapel?->nama ?? '-')
          : ($c?->nama_kegiatan ?? ''),
        'sub' => $c?->pengampu
          ? ($c->pengampu->ptk?->nama_lengkap ?? '')
          : ($c?->pembina?->nama_lengkap ?? ''),
      ];
    }
    foreach ($gw['lainnya'] as $evs) {
      foreach ($evs as $ev) {
        $blok[] = [
          'mulai' => $ev['waktu'][0],
          'selesai' => $ev['waktu'][1],
          'jenis' => $ev['jenis'],
          'label' => $ev['nama'],
          'sub' => '',
        ];
      }
    }
    usort($blok, fn($a, $b) => strcmp($a['mulai'], $b['mulai']));
    return $blok;
  }

  private function keMenit(string $w): int
  {
    [$j, $m] = array_map('intval', explode('.', str_replace(':', '.', $w)));
    return $j * 60 + $m;
  }

  // ── Helper ──────────────────────────────────────────────────────────

  private function daftarWebPublik()
  {
    return WebSekolah::with(['sekolah.detailTerbaru', 'sekolah.asets'])
      ->where('is_aktif', true)
      ->get()
      ->filter(fn(WebSekolah $w) => $w->sekolah && $w->sekolah->hasFitur('web-sekolah'))
      ->values();
  }

  private function cariWebPublik(string $slug): WebSekolah
  {
    $web = WebSekolah::with(['sekolah.detailTerbaru', 'sekolah.asets'])
      ->where('slug', $slug)
      ->where('is_aktif', true)
      ->first();

    abort_if(!$web || !$web->tampilPublik(), 404);

    return $web;
  }

  /** Data bersama semua halaman publik satu sekolah: identitas + statistik hidup. */
  private function dataSekolah(WebSekolah $web): array
  {
    $sekolah = $web->sekolah;
    $detail = $sekolah->detailTerbaru;
    $semesterAktif = Semester::where('is_active', true)->first();

    // Statistik mengikuti cara hitung DashboardController (non-Admin)
    // supaya angka publik sama dengan angka dashboard internal.
    $jumlahSiswa = $sekolah->siswaRegistrasi()->where('status', 'aktif')->count();
    $jumlahRombel = $sekolah->rombel()
      ->when($semesterAktif, fn($q) => $q->where('semester_id', $semesterAktif->id))
      ->count();
    $jumlahGuru = $sekolah->ptk()->where('jabatan', 'Guru')->count();
    $jumlahTendik = $sekolah->ptk()->where('jabatan', '!=', 'Guru')->count();

    return [
      'web' => $web,
      'sekolah' => $sekolah,
      'detail' => $detail,
      'namaSekolah' => $detail->nama_sekolah ?? 'Sekolah',
      'semesterAktif' => $semesterAktif,
      'statistik' => [
        'siswa' => $jumlahSiswa,
        'guru' => $jumlahGuru,
        'tendik' => $jumlahTendik,
        'ptk' => $jumlahGuru + $jumlahTendik,
        'rombel' => $jumlahRombel,
      ],
      'punyaLogo' => $sekolah->asets->where('tipe', SekolahLogo::TIPE_LOGO_SEKOLAH)->isNotEmpty(),
    ];
  }
}
