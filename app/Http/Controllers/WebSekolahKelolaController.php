<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use App\Support\KompresGambar;
use App\Models\WebBerita;
use App\Models\WebDokumen;
use App\Models\WebGaleri;
use App\Models\WebGaleriKegiatan;
use App\Models\WebSekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebSekolahKelolaController extends Controller
{
  /*
  |--------------------------------------------------------------------------
  | Kelola Web Sekolah — Operator Sekolah untuk sekolahnya sendiri
  | (pola yang sama dengan SekolahProfilController & SekolahLogoController).
  | Route-nya digerbang role:Operator Sekolah + fitur:web-sekolah.
  |--------------------------------------------------------------------------
  */

  private function sekolahSaya(): Sekolah
  {
    $sekolah = Auth::user()?->ptk?->sekolah;
    abort_if(!$sekolah, 403, 'Akun Anda tidak terhubung ke sekolah.');

    return $sekolah;
  }

  // ── Pengaturan ──────────────────────────────────────────────────────

  public function pengaturan()
  {
    $sekolah = $this->sekolahSaya();
    $web = $sekolah->webSekolah;

    // Saran slug dihitung di sini, BUKAN di Blade: Blade file ini tidak
    // mengimpor Illuminate\Support\Str, dan panggilan Str::slug() di
    // view hanya terpicu saat $web masih null (kunjungan pertama) —
    // di luar jalur itu error-nya tidak terlihat. (Class "Str" not
    // found, dilaporkan Mark 2026-11-20.)
    $slugSaran = $web?->slug
      ?? Str::slug($sekolah->detailTerbaru->nama_sekolah ?? '');

    return view('content.web-sekolah.pengaturan', compact('sekolah', 'web', 'slugSaran'));
  }

  public function simpanPengaturan(Request $request)
  {
    $sekolah = $this->sekolahSaya();
    $webAda = $sekolah->webSekolah;

    $validated = $request->validate([
      'slug' => [
        'required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
        Rule::unique('tabel_web_sekolah', 'slug')->ignore($webAda?->id),
        Rule::notIn(WebSekolah::SLUG_TERLARANG),
      ],
      'is_aktif' => ['nullable', 'boolean'],
      'tagline' => ['nullable', 'string', 'max:255'],
      'visi' => ['nullable', 'string'],
      'misi' => ['nullable', 'string'],
      'sambutan' => ['nullable', 'string'],
      'akreditasi' => ['nullable', 'string', 'max:10'],
      'tahun_berdiri' => ['nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 1)],
      'foto_hero' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
      'hapus_foto_hero' => ['nullable', 'boolean'],
    ], [
      'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung (-).',
      'slug.unique' => 'Slug ini sudah dipakai sekolah lain.',
      'slug.not_in' => 'Slug ini terlarang karena bertabrakan dengan halaman internal.',
    ]);

    $data = collect($validated)->except(['foto_hero', 'hapus_foto_hero'])->all();
    $data['is_aktif'] = $request->boolean('is_aktif');

    $web = WebSekolah::firstOrNew(['sekolah_id' => $sekolah->id]);

    if ($request->boolean('hapus_foto_hero') && $web->foto_hero) {
      Storage::disk('public')->delete($web->foto_hero);
      $data['foto_hero'] = null;
    }

    if ($request->hasFile('foto_hero')) {
      if ($web->foto_hero) {
        Storage::disk('public')->delete($web->foto_hero);
      }
      $data['foto_hero'] = KompresGambar::simpan($request->file('foto_hero'), "web-sekolah/{$sekolah->id}", 1920);
    }

    $web->fill($data);
    $web->sekolah_id = $sekolah->id;
    $web->save();

    return redirect()->route('web-sekolah.kelola.pengaturan')
      ->with('success', 'Pengaturan web sekolah berhasil disimpan. Web Anda: ' . url('/sekolah/' . $web->slug));
  }

  // ── Berita ──────────────────────────────────────────────────────────

  public function beritaIndex()
  {
    $sekolah = $this->sekolahSaya();
    $berita = $sekolah->webBerita()->latest('updated_at')->paginate(15);

    return view('content.web-sekolah.berita-index', compact('sekolah', 'berita'));
  }

  public function beritaCreate()
  {
    $sekolah = $this->sekolahSaya();
    $berita = new WebBerita(['status' => WebBerita::STATUS_DRAF]);

    return view('content.web-sekolah.berita-form', compact('sekolah', 'berita'));
  }

  public function beritaStore(Request $request)
  {
    $sekolah = $this->sekolahSaya();
    $data = $this->validasiBerita($request);

    $data['sekolah_id'] = $sekolah->id;
    $data['user_id'] = Auth::id();
    $data['slug'] = WebBerita::slugUnik($sekolah->id, $data['judul']);
    $data['diterbitkan_pada'] = $data['status'] === WebBerita::STATUS_TERBIT ? now() : null;

    if ($request->hasFile('foto')) {
      $data['foto'] = KompresGambar::simpan($request->file('foto'), "web-sekolah/{$sekolah->id}/berita", 1600);
    }

    WebBerita::create($data);

    return redirect()->route('web-sekolah.kelola.berita.index')
      ->with('success', 'Berita berhasil disimpan.');
  }

  public function beritaEdit(WebBerita $berita)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($berita->sekolah_id !== $sekolah->id, 404);

    return view('content.web-sekolah.berita-form', compact('sekolah', 'berita'));
  }

  public function beritaUpdate(Request $request, WebBerita $berita)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($berita->sekolah_id !== $sekolah->id, 404);

    $data = $this->validasiBerita($request);
    $data['slug'] = WebBerita::slugUnik($sekolah->id, $data['judul'], $berita->id);

    // Tanggal terbit diisi saat pertama kali diterbitkan, tidak di-reset saat edit.
    if ($data['status'] === WebBerita::STATUS_TERBIT && !$berita->diterbitkan_pada) {
      $data['diterbitkan_pada'] = now();
    }
    if ($data['status'] === WebBerita::STATUS_DRAF) {
      $data['diterbitkan_pada'] = null;
    }

    if ($request->hasFile('foto')) {
      if ($berita->foto) {
        Storage::disk('public')->delete($berita->foto);
      }
      $data['foto'] = KompresGambar::simpan($request->file('foto'), "web-sekolah/{$sekolah->id}/berita", 1600);
    }

    $berita->update($data);

    return redirect()->route('web-sekolah.kelola.berita.index')
      ->with('success', 'Berita berhasil diperbarui.');
  }

  public function beritaDestroy(WebBerita $berita)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($berita->sekolah_id !== $sekolah->id, 404);

    if ($berita->foto) {
      Storage::disk('public')->delete($berita->foto);
    }
    $berita->delete();

    return redirect()->route('web-sekolah.kelola.berita.index')
      ->with('success', 'Berita berhasil dihapus.');
  }

  private function validasiBerita(Request $request): array
  {
    return $request->validate([
      'judul' => ['required', 'string', 'max:255'],
      'isi' => ['required', 'string'],
      'status' => ['required', Rule::in([WebBerita::STATUS_DRAF, WebBerita::STATUS_TERBIT])],
      'foto' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
    ], [
      'foto.max' => 'Ukuran foto tidak boleh lebih dari 10 MB.',
    ]);
  }

  // ── Galeri ──────────────────────────────────────────────────────────

  public function galeriIndex()
  {
    $sekolah = $this->sekolahSaya();
    // Halaman galeri kini berbentuk DAFTAR ALBUM (permintaan Mark
    // 2026-11-23): tiap album tampil dengan beberapa foto pratinjau;
    // menambah/mengelola foto dilakukan di halaman edit albumnya.
    $daftarKegiatan = $sekolah->webGaleriKegiatan()
      ->withCount('galeri')
      ->with(['galeri' => fn ($q) => $q->orderBy('urutan')->latest('id')])
      ->orderByRaw('tanggal IS NULL')
      ->orderByDesc('tanggal')
      ->latest('id')
      ->get();
    $fotoLainnya = $sekolah->webGaleri()
      ->whereNull('kegiatan_id')
      ->orderBy('urutan')
      ->latest('id')
      ->get();

    return view('content.web-sekolah.galeri-index', compact('sekolah', 'daftarKegiatan', 'fotoLainnya'));
  }

  /** Halaman kelola satu album: rincian album + unggah & atur fotonya. */
  public function albumEdit(WebGaleriKegiatan $kegiatan)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($kegiatan->sekolah_id !== $sekolah->id, 404);

    $foto = $kegiatan->galeri()->get();
    $daftarKegiatan = $sekolah->webGaleriKegiatan()->orderBy('nama')->get();

    return view('content.web-sekolah.galeri-album-edit', compact('sekolah', 'kegiatan', 'foto', 'daftarKegiatan'));
  }

  /** Halaman kelola foto tanpa album ("Foto Lainnya"). */
  public function tanpaAlbum()
  {
    $sekolah = $this->sekolahSaya();

    $kegiatan = null;
    $foto = $sekolah->webGaleri()
      ->whereNull('kegiatan_id')
      ->orderBy('urutan')
      ->latest('id')
      ->get();
    $daftarKegiatan = $sekolah->webGaleriKegiatan()->orderBy('nama')->get();

    return view('content.web-sekolah.galeri-album-edit', compact('sekolah', 'kegiatan', 'foto', 'daftarKegiatan'));
  }

  public function galeriStore(Request $request)
  {
    $sekolah = $this->sekolahSaya();

    $request->validate([
      'foto' => ['required', 'array', 'min:1'],
      'foto.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
      'judul' => ['nullable', 'string', 'max:255'],
      'kegiatan_id' => ['nullable', 'integer', Rule::exists('tabel_web_galeri_kegiatan', 'id')->where('sekolah_id', $sekolah->id)],
      'kegiatan_baru' => ['nullable', 'string', 'max:255'],
    ], [
      'foto.*.max' => 'Ukuran tiap foto tidak boleh lebih dari 10 MB — foto akan dikompresi otomatis saat tersimpan.',
      'foto.*.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
    ]);

    // Album tujuan: nama baru yang diisi menang atas pilihan dropdown;
    // kosong keduanya berarti foto tanpa album ("Foto Lainnya").
    $kegiatanId = null;
    if ($request->filled('kegiatan_baru')) {
      $kegiatanId = $sekolah->webGaleriKegiatan()->create([
        'nama' => $request->kegiatan_baru,
      ])->id;
    } elseif ($request->filled('kegiatan_id')) {
      $kegiatanId = (int) $request->kegiatan_id;
    }

    $urutan = (int) ($sekolah->webGaleri()->max('urutan') ?? 0);

    foreach ($request->file('foto') as $file) {
      $path = KompresGambar::simpan($file, "web-sekolah/{$sekolah->id}/galeri", 1600);
      WebGaleri::create([
        'sekolah_id' => $sekolah->id,
        'kegiatan_id' => $kegiatanId,
        'judul' => $request->judul,
        'foto' => $path,
        'urutan' => ++$urutan,
      ]);
    }

    if ($kegiatanId) {
      return redirect()->route('web-sekolah.kelola.galeri.album.edit', $kegiatanId)
        ->with('success', 'Foto galeri berhasil diunggah.');
    }

    return redirect()->route('web-sekolah.kelola.galeri.tanpa-album')
      ->with('success', 'Foto galeri berhasil diunggah.');
  }

  public function galeriUpdate(Request $request, WebGaleri $galeri)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($galeri->sekolah_id !== $sekolah->id, 404);

    $data = $request->validate([
      'judul' => ['nullable', 'string', 'max:255'],
      'keterangan' => ['nullable', 'string', 'max:255'],
      'urutan' => ['nullable', 'integer', 'min:0'],
      'kegiatan_id' => ['nullable', 'integer', Rule::exists('tabel_web_galeri_kegiatan', 'id')->where('sekolah_id', $sekolah->id)],
    ]);

    $galeri->update($data);

    return redirect()->back(302, [], route('web-sekolah.kelola.galeri.index'))
      ->with('success', 'Foto galeri berhasil diperbarui.');
  }

  public function galeriDestroy(WebGaleri $galeri)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($galeri->sekolah_id !== $sekolah->id, 404);

    Storage::disk('public')->delete($galeri->foto);
    $galeri->delete();

    return redirect()->back(302, [], route('web-sekolah.kelola.galeri.index'))
      ->with('success', 'Foto galeri berhasil dihapus.');
  }

  // ── Album Kegiatan (pengelompokan galeri) ─────────────────────────
  public function kegiatanStore(Request $request)
  {
    $sekolah = $this->sekolahSaya();

    $data = $request->validate([
      'nama' => ['required', 'string', 'max:255'],
      'tanggal' => ['nullable', 'date'],
      'keterangan' => ['nullable', 'string'],
      'judul' => ['nullable', 'string', 'max:255'],
      'foto' => ['nullable', 'array'],
      'foto.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:10240'],
    ], [
      'foto.*.max' => 'Ukuran tiap foto tidak boleh lebih dari 10 MB — foto akan dikompresi otomatis saat tersimpan.',
    ]);

    $kegiatan = $sekolah->webGaleriKegiatan()->create(collect($data)->only(['nama', 'tanggal', 'keterangan'])->all());

    if ($request->hasFile('foto')) {
      $urutan = (int) ($sekolah->webGaleri()->max('urutan') ?? 0);
      foreach ($request->file('foto') as $file) {
        WebGaleri::create([
          'sekolah_id' => $sekolah->id,
          'kegiatan_id' => $kegiatan->id,
          'judul' => $data['judul'] ?? null,
          'foto' => KompresGambar::simpan($file, "web-sekolah/{$sekolah->id}/galeri", 1600),
          'urutan' => ++$urutan,
        ]);
      }
    }

    return redirect()->route('web-sekolah.kelola.galeri.album.edit', $kegiatan)
      ->with('success', 'Album kegiatan berhasil dibuat.');
  }

  public function kegiatanUpdate(Request $request, WebGaleriKegiatan $kegiatan)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($kegiatan->sekolah_id !== $sekolah->id, 404);

    $data = $request->validate([
      'nama' => ['required', 'string', 'max:255'],
      'tanggal' => ['nullable', 'date'],
      'keterangan' => ['nullable', 'string'],
    ]);

    $kegiatan->update($data);

    return redirect()->route('web-sekolah.kelola.galeri.album.edit', $kegiatan)
      ->with('success', 'Album kegiatan berhasil diperbarui.');
  }

  public function kegiatanDestroy(WebGaleriKegiatan $kegiatan)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($kegiatan->sekolah_id !== $sekolah->id, 404);

    // Foto TIDAK ikut terhapus — albumnya saja yang dibubarkan,
    // foto-fotonya kembali menjadi tanpa album.
    $kegiatan->galeri()->update(['kegiatan_id' => null]);
    $kegiatan->delete();

    return redirect()->route('web-sekolah.kelola.galeri.index')
      ->with('success', 'Album kegiatan dihapus — foto-fotonya tetap ada sebagai Foto Lainnya.');
  }

  // ── Dokumen Publik ────────────────────────────────────────────────
  // Sertifikat akreditasi, KOSP, RKT, RKJM, RKAS, dll. Berkas di disk
  // public, dialirkan ke publik lewat WebSekolahController (lihat/unduh).

  public function dokumenIndex()
  {
    $sekolah = $this->sekolahSaya();
    $dokumen = $sekolah->webDokumen()
      ->orderByRaw("CASE kategori WHEN 'akreditasi' THEN 0 WHEN 'kurikulum' THEN 1 WHEN 'perencanaan' THEN 2 WHEN 'kalender' THEN 3 ELSE 4 END")
      ->orderByDesc('tahun')
      ->orderBy('judul')
      ->get();

    return view('content.web-sekolah.dokumen-index', compact('sekolah', 'dokumen'));
  }

  public function dokumenStore(Request $request)
  {
    $sekolah = $this->sekolahSaya();

    $data = $request->validate([
      'judul' => ['required', 'string', 'max:255'],
      'kategori' => ['required', Rule::in(array_keys(WebDokumen::KATEGORI))],
      'tahun' => ['nullable', 'integer', 'min:1990', 'max:2100'],
      'keterangan' => ['nullable', 'string', 'max:255'],
      'berkas' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ]);

    $file = $request->file('berkas');
    $path = $file->store("web-sekolah/{$sekolah->id}/dokumen", 'public');

    WebDokumen::create([
      'sekolah_id' => $sekolah->id,
      'judul' => $data['judul'],
      'kategori' => $data['kategori'],
      'tahun' => $data['tahun'] ?? null,
      'keterangan' => $data['keterangan'] ?? null,
      'berkas' => $path,
      'nama_berkas' => $file->getClientOriginalName(),
      'ukuran' => $file->getSize(),
      'is_tampil' => $request->boolean('is_tampil'),
    ]);

    return redirect()->route('web-sekolah.kelola.dokumen.index')
      ->with('success', 'Dokumen berhasil ditambahkan.');
  }

  public function dokumenUpdate(Request $request, WebDokumen $dokumen)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($dokumen->sekolah_id !== $sekolah->id, 404);

    $data = $request->validate([
      'judul' => ['required', 'string', 'max:255'],
      'kategori' => ['required', Rule::in(array_keys(WebDokumen::KATEGORI))],
      'tahun' => ['nullable', 'integer', 'min:1990', 'max:2100'],
      'keterangan' => ['nullable', 'string', 'max:255'],
      'berkas' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
    ]);

    $dokumen->judul = $data['judul'];
    $dokumen->kategori = $data['kategori'];
    $dokumen->tahun = $data['tahun'] ?? null;
    $dokumen->keterangan = $data['keterangan'] ?? null;
    $dokumen->is_tampil = $request->boolean('is_tampil');

    // Ganti berkas hanya bila ada unggahan baru; berkas lama dihapus.
    if ($request->hasFile('berkas')) {
      Storage::disk('public')->delete($dokumen->berkas);
      $file = $request->file('berkas');
      $dokumen->berkas = $file->store("web-sekolah/{$sekolah->id}/dokumen", 'public');
      $dokumen->nama_berkas = $file->getClientOriginalName();
      $dokumen->ukuran = $file->getSize();
    }

    $dokumen->save();

    return redirect()->route('web-sekolah.kelola.dokumen.index')
      ->with('success', 'Dokumen berhasil diperbarui.');
  }

  public function dokumenDestroy(WebDokumen $dokumen)
  {
    $sekolah = $this->sekolahSaya();
    abort_if($dokumen->sekolah_id !== $sekolah->id, 404);

    Storage::disk('public')->delete($dokumen->berkas);
    $dokumen->delete();

    return redirect()->route('web-sekolah.kelola.dokumen.index')
      ->with('success', 'Dokumen berhasil dihapus.');
  }
}
