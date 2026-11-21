<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\Ptk;
use App\Models\SekolahLogo;
use App\Models\Semester;
use App\Models\WebBerita;
use App\Models\WebGaleri;
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
  // 1 web aktif  → langsung tampilkan sekolah itu.
  // >1 web aktif → direktori pilih sekolah.
  // 0            → perilaku lama: redirect ke home (login).
  public function beranda()
  {
    $webs = $this->daftarWebPublik();

    if ($webs->count() === 1) {
      return $this->tampil($webs->first()->slug);
    }

    if ($webs->count() > 1) {
      return view('publik.web-sekolah.direktori', [
        'webs' => $webs,
      ]);
    }

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

    $data['galeri'] = $web->sekolah->webGaleri()
      ->orderBy('urutan')
      ->latest('id')
      ->take(12)
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
