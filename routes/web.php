<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DhSiswaController;
use App\Http\Controllers\FormulirBuilderController;
use App\Http\Controllers\FormulirController;
use App\Http\Controllers\FormulirPublikController;
use App\Http\Controllers\FormulirSubmissionController;
use App\Http\Controllers\JadwalPelajaranController;
use App\Http\Controllers\KelolaBosController;
use App\Http\Controllers\KalenderPendidikanController;
use App\Http\Controllers\KartuPelajarController;
use App\Http\Controllers\KartuUjianController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LearningModuleController;
use App\Http\Controllers\LearningQuizController;
use App\Http\Controllers\LearningTopicController;
use App\Http\Controllers\MapelMappingController;
use App\Http\Controllers\MapelMasterController;
use App\Http\Controllers\pages\UserManagement;
use App\Http\Controllers\PresensiController;
use App\Http\Controllers\PtkController;
use App\Http\Controllers\RombelAnggotaController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\SekolahLogoController;
use App\Http\Controllers\PembaruanController;
use App\Http\Controllers\SekolahProfilController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SkhuController;
use App\Http\Controllers\SklController;
use App\Http\Controllers\SklKomponenController;
use App\Http\Controllers\SklKonfigurasiController;
use App\Http\Controllers\SklKomponenSubController;
use App\Http\Controllers\SuratKeluarController;
use App\Http\Controllers\SuratPindahController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidasiKelulusan;
use App\Http\Controllers\WaliKelasController;
use App\Http\Controllers\WebSekolahController;
use App\Http\Controllers\WebSekolahKelolaController;
use App\Http\Controllers\WilayahController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

// Wilayah (Dropdown Alamat)
Route::controller(WilayahController::class)->group(function () {
  Route::get('/get-provinsi', 'getProvinsi')->name('wilayah.provinsi');
  Route::get('/get-kabupaten/{province_id}', 'getKabupaten')->name('wilayah.kabupaten');
  Route::get('/get-kecamatan/{regency_id}', 'getKecamatan')->name('wilayah.kecamatan');
  Route::get('/get-desa/{district_id}', 'getDesa')->name('wilayah.desa');
});

// Root: selalu redirect ke home (login/dashboard) — keputusan Mark
// 2026-11-21. Landing page Web Sekolah TIDAK tampil di root; ia dibuka
// lewat slug sekolahnya: /sekolah/{slug}.
Route::get('/', [WebSekolahController::class, 'beranda'])->name('web-sekolah.beranda');
Route::get('/dashboard', fn() => redirect()->route('home'));

// Cek SKHU Publik
Route::get('/validasi-kelulusan', [ValidasiKelulusan::class, 'index'])->name('validasi-kelulusan.index');


/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
  Route::get('/home', [DashboardController::class, 'index'])->name('home');
});


/*
|--------------------------------------------------------------------------
| USER PROFILE & USER MANAGEMENT
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {

  // Profil User (semua user login)
  Route::controller(UserController::class)->group(function () {
    Route::get('/user/profile', 'index')->name('user.profil.index');
    Route::put('/user/profile', 'update')->name('user.profil.update');
    Route::put('/user/profile/password', 'updatePassword')->name('user.profil.password');
  });

  // Manajemen User
  Route::controller(UserManagement::class)
    ->prefix('user')
    ->name('user.')
    ->group(function () {
      Route::get('/laravel', 'UserManagement')->name('index');
      Route::post('/reset-password/{id}', 'resetPassword')->name('reset-password');
    });

  Route::resource('/user-list', UserManagement::class);
});


/*
|--------------------------------------------------------------------------
| SEKOLAH
|--------------------------------------------------------------------------
*/
Route::controller(SekolahController::class)
  ->prefix('sekolah')
  ->name('sekolah.')
  ->middleware(['auth:sanctum', 'role:Admin', 'semester.session'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/tabel', 'tabel')->name('data');
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
    Route::post('/{id}/generate-akun', 'generateAkun')->name('generate-akun');
    Route::get('/{sekolah_id}/fitur', 'getFitur')->name('fitur');
    Route::post('/fitur/toggle', 'toggleFitur')->name('fitur.toggle');
  });

Route::controller(SekolahProfilController::class)
  ->prefix('profil-sekolah')
  ->name('profil-sekolah.')
  ->middleware(['auth:sanctum', 'role:Operator Sekolah', 'semester.session'])
  ->group(function () {
    Route::get('/', 'edit')->name('edit');
    Route::put('/', 'update')->name('update');
  });

/*
|--------------------------------------------------------------------------
| WEB SEKOLAH — halaman publik per sekolah (fitur: web-sekolah)
|--------------------------------------------------------------------------
| SENGAJA didaftarkan SETELAH grup route /sekolah (Admin) di atas:
| route literal /sekolah/tabel milik Admin harus menang lebih dulu,
| slug web publik tidak boleh menelannya. Parameter memakai nama
| {slugWeb}/{slugBerita} agar tidak terjadi implicit model binding.
| Gerbang fitur diperiksa di controller (tamu tidak punya user).
*/
Route::controller(WebSekolahController::class)->group(function () {
  Route::get('/sekolah/{slugWeb}', 'tampil')->name('web-sekolah.tampil');
  Route::get('/sekolah/{slugWeb}/berita', 'beritaIndex')->name('web-sekolah.berita.index');
  Route::get('/sekolah/{slugWeb}/berita/{slugBerita}', 'beritaTampil')->name('web-sekolah.berita.tampil');
  Route::get('/sekolah/{slugWeb}/galeri/{kegiatan}', 'galeriAlbum')->name('web-sekolah.galeri.album');
  Route::get('/sekolah/{slugWeb}/foto-hero', 'fotoHero')->name('web-sekolah.foto-hero');
  Route::get('/sekolah/{slugWeb}/logo', 'logo')->name('web-sekolah.logo');
  Route::get('/web-berita/{berita}/foto', 'fotoBerita')->name('web-sekolah.berita.foto');
  Route::get('/web-galeri/{galeri}/foto', 'fotoGaleri')->name('web-sekolah.galeri.foto');
  Route::get('/web-dokumen/{dokumen}/lihat', 'dokumenLihat')->name('web-sekolah.dokumen.lihat');
  Route::get('/web-dokumen/{dokumen}/unduh', 'dokumenUnduh')->name('web-sekolah.dokumen.unduh');
  Route::get('/web-ptk/{ptk}/foto', 'fotoPtk')->name('web-sekolah.ptk.foto');
});

/*
|--------------------------------------------------------------------------
| WEB SEKOLAH — kelola (Operator Sekolah sekolahnya sendiri)
|--------------------------------------------------------------------------
*/
Route::controller(WebSekolahKelolaController::class)
  ->prefix('web-sekolah')
  ->name('web-sekolah.kelola.')
  ->middleware(['auth:sanctum', 'role:Operator Sekolah', 'fitur:web-sekolah', 'semester.session'])
  ->group(function () {
    Route::get('/pengaturan', 'pengaturan')->name('pengaturan');
    Route::put('/pengaturan', 'simpanPengaturan')->name('pengaturan.simpan');

    Route::get('/berita', 'beritaIndex')->name('berita.index');
    Route::get('/berita/buat', 'beritaCreate')->name('berita.create');
    Route::post('/berita', 'beritaStore')->name('berita.store');
    Route::get('/berita/{berita}/edit', 'beritaEdit')->name('berita.edit');
    Route::put('/berita/{berita}', 'beritaUpdate')->name('berita.update');
    Route::delete('/berita/{berita}', 'beritaDestroy')->name('berita.destroy');

    Route::get('/galeri', 'galeriIndex')->name('galeri.index');
    Route::post('/galeri', 'galeriStore')->name('galeri.store');
    Route::get('/galeri/album/{kegiatan}/edit', 'albumEdit')->name('galeri.album.edit');
    Route::get('/galeri/tanpa-album', 'tanpaAlbum')->name('galeri.tanpa-album');
    Route::post('/galeri/kegiatan', 'kegiatanStore')->name('galeri.kegiatan.store');
    Route::put('/galeri/kegiatan/{kegiatan}', 'kegiatanUpdate')->name('galeri.kegiatan.update');
    Route::delete('/galeri/kegiatan/{kegiatan}', 'kegiatanDestroy')->name('galeri.kegiatan.destroy');
    Route::post('/galeri/kegiatan/{kegiatan}/tampil', 'kegiatanToggleTampil')->name('galeri.kegiatan.tampil');
    Route::put('/galeri/{galeri}', 'galeriUpdate')->name('galeri.update');
    Route::delete('/galeri/{galeri}', 'galeriDestroy')->name('galeri.destroy');

    Route::get('/dokumen', 'dokumenIndex')->name('dokumen.index');
    Route::post('/dokumen', 'dokumenStore')->name('dokumen.store');
    Route::put('/dokumen/{dokumen}', 'dokumenUpdate')->name('dokumen.update');
    Route::delete('/dokumen/{dokumen}', 'dokumenDestroy')->name('dokumen.destroy');
  });

/*
|--------------------------------------------------------------------------
| PEMBARUAN APLIKASI (Admin) — pemutakhiran tanpa SSH untuk
| hosting bersama: unggah paket .zip, aplikasi membackup file
| lama, menimpa, menjalankan migrasi, dan membersihkan cache.
| Path 'pembaruan' dikecualikan dari kunci semester global.
|--------------------------------------------------------------------------
*/
Route::controller(PembaruanController::class)
  ->prefix('pembaruan')
  ->name('pembaruan.')
  ->middleware(['auth:sanctum', 'role:Admin'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/terapkan', 'terapkan')->name('terapkan');
    Route::post('/pulihkan', 'pulihkan')->name('pulihkan');
  });


/*
|--------------------------------------------------------------------------
| SEMESTER
|--------------------------------------------------------------------------
*/
Route::controller(SemesterController::class)
  ->prefix('semester')
  ->name('semester.')
  ->middleware(['auth:sanctum', 'role:Admin', 'semester.session'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/table', 'table')->name('table');
    Route::post('/{id}/kunci', 'kunci')->name('kunci');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
  });


/*
|--------------------------------------------------------------------------
| SISWA
|--------------------------------------------------------------------------
*/
Route::controller(SiswaController::class)
  ->prefix('siswa')
  ->name('siswa.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah|Guru'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{id}/show', 'show')->name('show');
    // Edit biodata juga untuk Guru — di controller dikurung ke
    // siswa rombel yang ia wali kelasi
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::put('/{id}', 'update')->name('update');
    Route::get('/tabel', 'tabel')->name('tabel');
    Route::get('/export', 'export')->name('export');
    Route::get('/alumni', 'alumni')->name('alumni');
    Route::get('/keluar', 'siswaKeluar')->name('keluar');

    Route::get('stats-pivot-usia', 'statsPivotUsia')->name('stats-pivot-usia');
    Route::get('stats-pivot-agama', 'statsPivotAgama')->name('stats-pivot-agama');
  });

Route::controller(SiswaController::class)
  ->prefix('siswa')
  ->name('siswa.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/download-template', 'downloadTemplate')->name('download-template');
    Route::post('/import', 'import')->name('import');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}/registrasi', 'registrasi')->name('registrasi');
    Route::post('/alumni/{id}/batal-lulus', 'batalkanKelulusan')->name('alumni.batal-lulus');
    Route::post('/keluar/{id}/batal', 'batalkanKeluar')->name('keluar.batal');
    Route::get('/cari-terdaftar', 'cariTerdaftar')->name('cari-terdaftar');
    Route::post('/mutasi-masuk', 'mutasiMasuk')->name('mutasi-masuk');
  });


/*
|--------------------------------------------------------------------------
| MAPEL MAPPING
|--------------------------------------------------------------------------
*/
Route::controller(MapelMappingController::class)
  ->prefix('mapel-mapping')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('mapel-mapping.index');
    Route::get('/table', 'table');
    Route::put('/{mapel}', 'update');
    Route::delete('/{mapel}', 'destroy');
    Route::post('/', 'store');
    Route::post('/reorder', 'reorder'); // NOTE: sebelumnya di luar group tanpa middleware sama sekali, sekarang ikut terlindungi
    Route::post('/salin-semester', 'salinSemester');
    Route::post('/salin-tambahan', 'salinTambahan');
    Route::post('/{mapel}/nonaktif', 'toggleNonaktif');
    Route::put('/master-lokal/{mapelMaster}', 'updateMasterLokal');
    // Tambah mapel baru (quick create)
    Route::post('quick-create', 'quickCreateMapel')->name('quick-create');
  });


/*
|--------------------------------------------------------------------------
| MAPEL MASTER
|--------------------------------------------------------------------------
*/
Route::controller(MapelMasterController::class)
  ->prefix('mapel')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('mapel.index');
    Route::get('/table', 'table');
    Route::post('/', 'store');
    Route::put('/{mapelMaster}', 'update');
    Route::delete('/{mapelMaster}', 'destroy');
  });


/*
|--------------------------------------------------------------------------
| PTK
|--------------------------------------------------------------------------
*/
// Foto PTK dialirkan lewat route untuk SEMUA pengguna yang sudah login
// (avatar navbar tampil juga untuk Guru) — jadi route ini di luar grup
// role Admin|Operator Sekolah di bawah. SekolahScope pada model tetap
// membatasi PTK yang bisa diakses sesuai sekolah pengguna.
Route::get('/ptk/{ptk}/foto', [PtkController::class, 'foto'])
  ->middleware('auth:sanctum')
  ->name('ptk.foto');

// Foto siswa dialirkan lewat route yang sama polanya (daftar SKHU, SKL,
// Ijazah memakai asset('storage/...') yang pecah di hosting tanpa
// symlink). Di luar grup role siswa di bawah supaya Guru pun bisa
// memuat avatar daftar; pembatasan sekolahnya ada di controller
// (SiswaController::foto), karena model Siswa tidak memakai SekolahScope.
Route::get('/siswa/{siswa}/foto', [SiswaController::class, 'foto'])
  ->middleware('auth:sanctum')
  ->name('siswa.foto');

Route::controller(PtkController::class)
  ->prefix('ptk')
  ->name('ptk.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/data', 'data')->name('data');
    Route::get('/create', 'buat')->name('create');
    Route::get('/{id}/ubah', 'ubah')->name('ubah');
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
    Route::post('/{id}/generate-akun', 'generateAkun')->name('generate-akun');
    Route::post('/{id}/status', 'updateStatus')->name('status');
    Route::get('/{id}/pangkat', 'daftarPangkat')->name('pangkat.index');
    Route::post('/{id}/pangkat', 'simpanPangkat')->name('pangkat.store');
    Route::delete('/{id}/pangkat/{riwayatId}', 'hapusPangkat')->name('pangkat.destroy');
  });


/*
|--------------------------------------------------------------------------
| ROMBEL
|--------------------------------------------------------------------------
*/
Route::controller(RombelController::class)
  ->prefix('rombel')
  ->name('rombel.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/tabel', 'tabel')->name('tabel');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
  });

// Anggota Rombel (bagian dari menu Rombel)
Route::controller(RombelAnggotaController::class)
  ->prefix('anggota-rombel')
  ->name('anggota-rombel.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/{rombel_id}/list', 'rombelAnggota')->name('list');
    Route::get('/{rombel_id}/tersedia', 'listSiswaTersedia')->name('tersedia');
    Route::get('/{siswa_id}/riwayat', 'riwayatSiswa')->name('riwayat');
    Route::post('/', 'store')->name('store');
    Route::post('/hapus-massal', 'destroyMassal')->name('hapus-massal');
    Route::delete('/{id}', 'destroy')->name('destroy');
  });


/*
|--------------------------------------------------------------------------
| WALI KELAS
|--------------------------------------------------------------------------
*/
Route::controller(WaliKelasController::class)
  ->prefix('wali-kelas')
  ->name('wali.kelas.')
  ->middleware(['auth:sanctum', 'role:Guru'])
  ->group(function () {
    Route::get('/{rombel_id}/siswa', 'dataSiswa')->name('siswa');
    Route::get('/{rombel_id}/absensi', 'absensi')->name('absensi');
  });

Route::get('wali-kelas/{rombel_id}/tabelSiswa', [SiswaController::class, 'tabel'])
  ->middleware(['auth:sanctum', 'role:Guru']);


/*
|--------------------------------------------------------------------------
| SEKOLAH LOGO
|--------------------------------------------------------------------------
*/
Route::controller(SekolahLogoController::class)
  ->prefix('logo')
  ->middleware(['auth:sanctum', 'role:Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('logo');
    Route::get('/lihat/{tipe}', 'lihat');
    Route::post('/upload', 'upload');
    Route::post('/hapus', 'hapus');
  });


/*
|--------------------------------------------------------------------------
| SKL (Surat Keterangan Lulus)
|--------------------------------------------------------------------------
*/
Route::middleware(['fitur:skl'])->group(function () {
  // SKL - Komponen & Sub-Komponen (Master, Admin Only)
  Route::controller(SklKomponenController::class)
    ->prefix('skl/komponen')
    ->name('skl.komponen.')
    ->middleware(['auth:sanctum', 'role:Admin', 'semester.session'])
    ->group(function () {
      Route::get('/', 'index')->name('index');
      Route::get('/create', 'create')->name('create');
      Route::post('/', 'store')->name('store');
      Route::post('/salin', 'salin')->name('salin');
      Route::get('/{komponen}/edit', 'edit')->name('edit');
      Route::put('/{komponen}', 'update')->name('update');
      Route::delete('/{komponen}', 'destroy')->name('destroy');

      // Sub-komponen (nested resource)
      Route::get('/{komponen}/sub', [SklKomponenSubController::class, 'index'])->name('sub.index');
      Route::post('/{komponen}/sub', [SklKomponenSubController::class, 'store'])->name('sub.store');
      Route::put('/{komponen}/sub/{sub}', [SklKomponenSubController::class, 'update'])->name('sub.update');
      Route::delete('/{komponen}/sub/{sub}', [SklKomponenSubController::class, 'destroy'])->name('sub.destroy');
    });

  // SKL - Persetujuan (Kepala Sekolah)
  Route::controller(SklController::class)
    ->prefix('skl')
    ->name('skl.')
    ->middleware(['auth:sanctum', 'role:Kepala Sekolah'])
    ->group(function () {
      Route::post('/bulk/setujui', 'bulkSetujui')->name('bulk.setujui');
      Route::post('/bulk/batal-setujui', 'bulkBatalSetujui')->name('bulk.batal_setujui');
      Route::post('/bulk/tolak', 'bulkTolak')->name('bulk.tolak');
      Route::post('/{skl}/setujui', 'setujui')->name('setujui');
      Route::post('/{skl}/tolak', 'tolak')->name('tolak');
    });

  // SKL - Konfigurasi Mode Input (Operator Sekolah, fitur: skl [asumsi, lihat catatan])
  Route::controller(SklKonfigurasiController::class)
    ->prefix('skl/konfigurasi')
    ->name('skl.konfigurasi.')
    ->middleware(['auth:sanctum', 'role:Operator Sekolah'])
    ->group(function () {
      Route::get('/', 'edit')->name('edit');
      Route::put('/', 'update')->name('update');
    });

  // SKL - Kelola (Operator Sekolah dan Guru wali kelas; grup ini
  // didaftarkan lebih dulu supaya /create tidak tertangkap pola
  // /{skl} milik grup baca)
  Route::controller(SklController::class)
    ->prefix('skl')
    ->name('skl.')
    ->middleware(['auth:sanctum', 'role:Operator Sekolah|Guru'])
    ->group(function () {
      Route::post('/bulk/ajukan', 'bulkAjukan')->name('bulk.ajukan');
      Route::delete('/bulk/hapus', 'bulkHapus')->name('bulk.hapus');
      Route::get('/create', 'create')->name('create');
      Route::post('/', 'store')->name('store');
      Route::get('/{skl}/edit', 'edit')->name('edit');
      Route::put('/{skl}', 'update')->name('update');
      Route::delete('/{skl}', 'destroy')->name('destroy');
      Route::get('/{skl}/nilai', 'editNilai')->name('nilai.edit');
      Route::put('/{skl}/nilai', 'updateNilai')->name('nilai.update');
      Route::put('/{skl}/nomor', 'updateNomor')->name('nomor.update');
      Route::post('/{skl}/ajukan', 'ajukan')->name('ajukan');
      Route::post('/sync', 'sync')->name('sync');
      Route::get('/pengaturan-nomor', 'pengaturanNomor')->name('pengaturan-nomor');
      Route::post('/pengaturan-nomor', 'simpanPengaturanNomor')->name('pengaturan-nomor.simpan');
      Route::get('/template/download', 'downloadTemplate')->name('template.download');
      Route::get('/leger/download', 'downloadLeger')->name('leger.download');
      Route::post('/import', 'importNilai')->name('import');
    });

  // SKL - Kelola Template Cetak (Admin; didaftarkan sebelum grup
  // baca supaya /template tidak tertangkap pola /{skl})
  Route::controller(SklController::class)
    ->prefix('skl')
    ->middleware(['auth:sanctum', 'role:Admin', 'fitur:skl'])
    ->group(function () {
      Route::get('/template', 'templateIndex');
      Route::post('/template', 'templateStore');
      Route::put('/template/{id}', 'templateUpdate');
      Route::delete('/template/{id}', 'templateDestroy');
      Route::post('/kunci', 'kunci')->name('skl.kunci');
    });

  // SKL - Baca (Admin dan Kepala Sekolah boleh melihat;
  // Kepala Sekolah bertindak lewat grup persetujuan)
  Route::controller(SklController::class)
    ->prefix('skl')
    ->name('skl.')
    ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah|Guru|Kepala Sekolah'])
    ->group(function () {
      Route::get('/bulk/cetak', 'generateBulk')->name('bulk.cetak');
      Route::get('/', 'index')->name('index');
      Route::get('/{skl}', 'show')->name('show');
      Route::get('/{id}/preview', 'previewPdf')->name('preview');
      Route::get('/{id}/download', 'generatePdf')->name('download');
    });
});
/*
|--------------------------------------------------------------------------
| SKHU
|--------------------------------------------------------------------------
*/
// SKHU - Baca (Admin boleh melihat; yang mengisi dibatasi di grup kelola)
Route::controller(SkhuController::class)
  ->prefix('skhu')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah|Guru|Kepala Sekolah', 'fitur:skhu'])
  ->group(function () {
    Route::get('/', 'index')->name('skhu');
    Route::get('/download-bulk', 'generateBulk');
    Route::get('/siswa/{siswa_id}', 'formSiswa')->name('skhu.form'); // Nama unik agar tidak bentrok dengan index 'skhu'
    Route::get('/pdf/{skhu_id}', 'generatePdf');
    Route::get('view/{skhu_id}', 'previewPdf');
  });

// SKHU - Kelola (Operator Sekolah dan Guru wali kelas; penjaga
// wali kelasnya ditegakkan di controller)
Route::controller(SkhuController::class)
  ->prefix('skhu')
  ->middleware(['auth:sanctum', 'role:Operator Sekolah|Guru', 'fitur:skhu'])
  ->group(function () {
    Route::post('/finalisasi-semua', 'finalisasiSemua');
    Route::post('/siswa/{siswa_id}/simpan', 'simpan');
    Route::post('/siswa/{skhu_id}/updateStatus', 'updateStatus');
    // Impor untuk wali kelas juga — baris di luar rombelnya
    // dilewati di dalam import (daftar putih per Guru)
    Route::get('/import/template', 'downloadTemplate');
    Route::get('/leger', 'downloadLeger');
    Route::post('/import', 'importNilai');
  });

// SKHU - Khusus Operator (halaman impor mandiri & pengaturan
// angkatan; impor + template juga terbuka untuk Guru lewat grup
// kelola di atas dengan kurungan rombel)
Route::controller(SkhuController::class)
  ->prefix('skhu')
  ->middleware(['auth:sanctum', 'role:Operator Sekolah', 'fitur:skhu'])
  ->group(function () {
    Route::get('/import', 'halamanImport');
    // Nama route berawalan 'skhu' WAJIB ada: penanda menu aktif
    // di sidebar mencocokkan awalan nama route dengan slug menu —
    // tanpa nama, highlight SKHU hilang di halaman ini
    Route::get('/pengaturan-angkatan', 'pengaturanAngkatan')->name('skhu.pengaturan-angkatan');
    Route::post('/pengaturan-angkatan', 'simpanPengaturanAngkatan')->name('skhu.simpan-pengaturan-angkatan');
  });

// SKHU - Kelola Template Cetak (Admin; template berlaku lintas sekolah
// untuk satu jenjang + semester, jadi bukan domain Operator)
Route::controller(SkhuController::class)
  ->prefix('skhu')
  ->middleware(['auth:sanctum', 'role:Admin', 'fitur:skhu'])
  ->group(function () {
    Route::get('/template', 'templateIndex');
    Route::post('/template', 'templateStore');
    Route::put('/template/{id}', 'templateUpdate');
    Route::delete('/template/{id}', 'templateDestroy');
    Route::post('/kunci', 'kunci')->name('skhu.kunci');
  });



/*
|--------------------------------------------------------------------------
| E-LEARNING
| (fitur: elearning [asumsi mencakup semua rute di bawah, lihat catatan])
|--------------------------------------------------------------------------
*/
Route::middleware(['fitur:elearning'])->group(function () {

  Route::get('/learning/demo-pernapasan', function () {
    return view('learning.demo-pernapasan');
  })->name('learnings.demo-pernapasan');

  // Player Pembelajaran Dinamis berdasarkan ID Topic
  Route::get('/learning/topic/{id}', [LearningController::class, 'showTopic'])->name('learning.topic.show');

  // Manajemen Topik/Modul Ajar
  Route::controller(LearningTopicController::class)->group(function () {
    Route::get('/learning/topics/create', 'create')->name('learning.topics.create');
    Route::get('/learning/topics', 'index')->name('learning.topics.index');
    Route::get('/learning/topics/{topic}/edit', 'edit')->name('learning.topics.edit');
    Route::put('/learning/topics/{topic}', 'updateInfo')->name('learning.topics.updateInfo');
    Route::delete('/learning/topics/{topic}', 'destroy')->name('learning.topics.destroy');
    Route::get('/learning/mapel-by-kelas/{kelas}', 'mapelByKelas')->name('learning.mapelByKelas');

    // Simpan info modul dulu sebagai draft (langkah 1)
    Route::post('/learning/topics/draft', 'storeDraft')->name('learning.topics.storeDraft');

    // CRUD 1 slide (dipakai berulang selama user nambah/edit/hapus slide)
    Route::post('/learning/topics/{topic}/slides', 'storeSlide')->name('learning.topics.slides.store');
    Route::put('/learning/topics/{topic}/slides/{slide}', 'updateSlide')->name('learning.topics.slides.update');
    Route::delete('/learning/topics/{topic}/slides/{slide}', 'destroySlide')->name('learning.topics.slides.destroy');

    // Finalisasi: draft -> published
    Route::post('/learning/topics/{topic}/publish', 'publish')->name('learning.topics.publish');
  });

  // Kuis
  Route::controller(LearningQuizController::class)->group(function () {
    Route::get('/learning/quiz/{topicId}', 'show')->name('learning.quiz.show');
    Route::post('/learning/quiz/{quizId}/submit', 'submit')->name('learning.quiz.submit');
  });
});


/*
|--------------------------------------------------------------------------
| KALENDER PENDIDIKAN (fitur: kaldik)
|--------------------------------------------------------------------------
*/
Route::controller(KalenderPendidikanController::class)
  ->prefix('kalender-pendidikan')
  ->name('kalender-pendidikan.')
  ->middleware(['auth:sanctum', 'fitur:kaldik'])
  ->group(function () {
    Route::get('/cetak', 'cetak')->name('cetak');
    Route::get('/cetak-tabel', 'cetakTabel')->name('cetak-tabel');
    Route::get('/import/template', 'downloadTemplateImport')->name('import-template');
    Route::post('/import', 'import')->name('import');
    Route::get('/', 'index')->name('index');
    Route::get('/tabel', 'tabel');
    Route::post('/', 'store');
    Route::put('/{kalenderPendidikan}', 'update');
    Route::delete('/{kalenderPendidikan}', 'destroy');
  });


/*
|--------------------------------------------------------------------------
| KARTU PELAJAR (fitur: kartu-pelajar)
|--------------------------------------------------------------------------
*/
Route::controller(KartuPelajarController::class)
  ->prefix('kartu-pelajar')
  ->name('kartu-pelajar.')
  ->middleware(['auth:sanctum', 'fitur:kartu-pelajar'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/data', 'data')->name('data');
    Route::post('/cetak', 'cetak')->name('cetak');
  });


/*
|--------------------------------------------------------------------------
| KARTU PESERTA UJIAN (fitur: kartu-ujian)
|--------------------------------------------------------------------------
*/
Route::controller(KartuUjianController::class)
  ->prefix('kartu-ujian')
  ->name('kartu-ujian.')
  ->middleware(['auth:sanctum', 'fitur:kartu-ujian'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/data', 'data')->name('data');
    Route::post('/cetak', 'cetak')->name('cetak');
  });


/*
|--------------------------------------------------------------------------
| JADWAL PELAJARAN (fitur: jadwal-pelajaran)
|--------------------------------------------------------------------------
| Melihat: Admin, Operator Sekolah, Kepala Sekolah, Guru.
| Menyusun (pengaturan jam, pengampu, isi kotak) hanya Operator
| Sekolah — peran lain mode lihat saja.
*/
Route::controller(JadwalPelajaranController::class)
  ->prefix('jadwal-pelajaran')
  ->name('jadwal-pelajaran.')
  ->middleware(['auth:sanctum', 'fitur:jadwal-pelajaran'])
  ->group(function () {
    Route::get('/', 'index')->name('index')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Guru');
    Route::get('/ekspor-pdf-kelas', 'eksporPdfKelas')->name('ekspor.pdf.kelas')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah');
    Route::get('/ekspor-pdf-guru', 'eksporPdfGuru')->name('ekspor.pdf.guru')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah');
    Route::get('/editor/{rombel}', 'editor')->name('editor')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Guru');
    Route::post('/editor/{rombel}/model', 'pilihModel')->name('rombel.model')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengaturan', 'pengaturan')->name('pengaturan')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengaturan/model', 'tambahModel')->name('pengaturan.model.tambah')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengaturan/baru', 'buatModel')->name('pengaturan.model.baru')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengaturan/baru', 'simpanModelBaru')->name('pengaturan.model.baru.simpan')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengaturan/{id}', 'pengaturanModel')->name('pengaturan.model')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengaturan/{id}', 'simpanPengaturan')->name('pengaturan.model.simpan')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengaturan/{id}/hapus', 'hapusModel')->name('pengaturan.model.hapus')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengaturan/{id}/template-impor', 'templateImpor')->name('pengaturan.model.template-impor')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengaturan/{id}/ekspor-excel', 'eksporExcel')->name('pengaturan.model.ekspor-excel')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengaturan/{id}/impor', 'imporJadwal')->name('pengaturan.model.impor')
      ->middleware('role:Operator Sekolah');
    Route::get('/pengampu/{rombel}', 'pengampu')->name('pengampu')
      ->middleware('role:Operator Sekolah');
    Route::post('/pengampu/{rombel}', 'simpanPengampu')->name('pengampu.simpan')
      ->middleware('role:Operator Sekolah');
    Route::post('/sel/simpan', 'simpanSel')->name('sel.simpan')
      ->middleware('role:Operator Sekolah');
    Route::post('/sel/hapus', 'hapusSel')->name('sel.hapus')
      ->middleware('role:Operator Sekolah');
  });


/*
|--------------------------------------------------------------------------
| DAFTAR HADIR SISWA (fitur: dh-siswa)
|--------------------------------------------------------------------------
*/
Route::controller(DhSiswaController::class)
  ->prefix('dh-siswa')
  ->name('dh-siswa.')
  ->middleware(['fitur:dh-siswa'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/cetak', 'cetak')->name('cetak');
  });

// routes/web.php
// Presensi - Baca (pengisi sesuai cakupannya + pemantau:
// Kepala Sekolah & Admin; kurungan per rombel ditegakkan
// di controller lewat bolehLihat)
Route::prefix('presensi')->name('presensi.')->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah|Guru|Kepala Sekolah', 'fitur:presensi'])->group(function () {
  Route::get('/', [PresensiController::class, 'index'])->name('index');
  Route::get('/ajax-siswa', [PresensiController::class, 'ajaxSiswa'])->name('ajaxSiswa');
  Route::get('/rekap', [PresensiController::class, 'rekap'])->name('rekap');
  Route::get('/rekap/excel', [PresensiController::class, 'rekapExcel'])->name('rekap.excel');
  Route::get('/rekap/pdf', [PresensiController::class, 'rekapPdf'])->name('rekap.pdf');
});

// Presensi - Simpan (hanya peran pengisi: Operator Sekolah &
// Guru wali kelas; kurungan rombel + jendela koreksi Guru
// ditegakkan di controller lewat bolehIsi)
Route::prefix('presensi')->name('presensi.')->middleware(['auth:sanctum', 'role:Operator Sekolah|Guru', 'fitur:presensi'])->group(function () {
  Route::post('/store', [PresensiController::class, 'store'])->name('store');
});

// Presensi - Pengaturan sekolah (khusus Operator Sekolah)
Route::prefix('presensi')->name('presensi.')->middleware(['auth:sanctum', 'role:Operator Sekolah', 'fitur:presensi'])->group(function () {
  Route::post('/pengaturan', [PresensiController::class, 'simpanPengaturan'])->name('pengaturan');
});

Route::middleware(['auth:sanctum', 'role:Operator Sekolah'])->group(function () {
  Route::get('/periodic/copy-form', [\App\Http\Controllers\DataPeriodikController::class, 'showCopyForm'])->name('periodic.copy-form');
  Route::post('/periodic/copy', [\App\Http\Controllers\DataPeriodikController::class, 'copyData'])->name('periodic.copy');
});

Route::get('/sekolah-belum-aktif', function () {
  return view('errors.sekolah-belum-aktif');
})->middleware('auth:sanctum')->name('sekolah.belum-aktif');

/*
|--------------------------------------------------------------------------
| SURAT KELUAR (fitur: surat-keluar)
|--------------------------------------------------------------------------
| Buku agenda surat keluar per sekolah. Diterbitkan langsung
| oleh petugas (Operator Sekolah / Tata Usaha) tanpa alur
| persetujuan. Kepala Sekolah mode lihat, Admin memantau per
| sekolah terpilih. Modul ini dikecualikan dari kunci semester
| global (digerakkan tanggal surat).
*/
Route::controller(SuratKeluarController::class)
  ->prefix('surat-keluar')
  ->name('surat-keluar.')
  ->middleware(['auth:sanctum', 'fitur:surat-keluar'])
  ->group(function () {
    Route::get('/', 'index')->name('index')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
    Route::get('/ekspor-excel', 'eksporExcel')->name('ekspor.excel')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
    Route::get('/cetak-agenda', 'cetakAgenda')->name('cetak.agenda')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
    Route::get('/buat', 'create')->name('create')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/', 'store')->name('store')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::get('/{surat}/ubah', 'edit')->name('edit')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
    Route::put('/{surat}', 'update')->name('update')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/{surat}/terbitkan', 'terbitkan')->name('terbitkan')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/{surat}/batalkan', 'batalkan')->name('batalkan')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::delete('/{surat}', 'hapus')->name('hapus')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/{surat}/berkas', 'berkasUnggah')->name('berkas.unggah')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::get('/berkas/{berkas}', 'berkasLihat')->name('berkas.lihat')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
    Route::delete('/berkas/{berkas}', 'berkasHapus')->name('berkas.hapus')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::get('/pengaturan', 'pengaturan')->name('pengaturan')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/pengaturan', 'simpanPengaturan')->name('pengaturan.simpan')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/pengaturan/kategori', 'kategoriSimpan')->name('kategori.simpan')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::delete('/pengaturan/kategori/{kategori}', 'kategoriHapus')->name('kategori.hapus')
      ->middleware('role:Operator Sekolah|Tata Usaha');
  });

/*
|--------------------------------------------------------------------------
| KELOLA BOS (fitur: kelola-bos) — Fase 1
|--------------------------------------------------------------------------
| Tampilan read-only cermin data ARKAS: Kertas Kerja
| (rapbs) dan BKU (kas_umum), disinkronkan agen dari laptop
| ARKAS lewat /api/bos/sinkron. Operator Sekolah, Kepala
| Sekolah & Bendahara (hanya membaca) melihat sekolahnya,
| Admin memantau per sekolah terpilih. Cermin bersifat
| hanya baca; penulisannya hanya lapisan kerja: nama
| alias tampilan (Fase 1.7) dan token agen sinkron
| (Fase 1.12).
*/
Route::controller(KelolaBosController::class)
  ->prefix('kelola-bos')
  ->name('kelola-bos.')
  ->middleware(['auth:sanctum', 'fitur:kelola-bos'])
  ->group(function () {
    Route::get('/kertas-kerja', 'kertasKerja')->name('kertas-kerja')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::get('/bku', 'bku')->name('bku')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    // SPJ Kwitansi (Fase 1) — cetak PDF kwitansi per nota
    // dari baris belanja BKU; mengikuti akses baca BKU.
    Route::get('/bku/kwitansi-massal', 'cetakKwitansiMassal')->name('bku.kwitansi-massal')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::get('/bku/kwitansi/{id}', 'cetakKwitansi')->name('bku.kwitansi')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    // SPJ Tanda Terima (Fase 2) — cetak PDF tanda terima
    // per grup non-barang dari baris belanja BKU;
    // mengikuti akses baca BKU.
    Route::get('/bku/tanda-terima/{id}', 'cetakTandaTerima')->name('bku.tanda-terima')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    // SPJ Penerima (Fase 2) — editor daftar penerima per
    // grup transaksi BKU (modal di halaman BKU, semua
    // JSON). Baca mengikuti akses BKU; tulis hanya Admin
    // & Operator Sekolah — kecuali penanda status
    // selesai (per penerima & per grup) yang juga boleh
    // diubah Bendahara. Semua URL membawa id baris BKU
    // agar konteks sekolah selalu berasal dari baris itu.
    Route::get('/bku/{id}/penerima', 'penerimaData')->name('bku.penerima.data')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::post('/bku/{id}/penerima/ptk', 'penerimaTambahPtk')->name('bku.penerima.ptk')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/{id}/penerima/master', 'penerimaTambahMaster')->name('bku.penerima.master')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/{id}/penerima', 'penerimaSimpan')->name('bku.penerima.simpan')
      ->middleware('role:Admin|Operator Sekolah');
    Route::put('/bku/{id}/penerima/{penerimaId}', 'penerimaUbah')->name('bku.penerima.ubah')
      ->middleware('role:Admin|Operator Sekolah');
    Route::put('/bku/{id}/penerima/{penerimaId}/selesai', 'penerimaSelesaiUbah')->name('bku.penerima.selesai')
      ->middleware('role:Admin|Operator Sekolah|Bendahara');
    Route::delete('/bku/{id}/penerima/{penerimaId}', 'penerimaHapus')->name('bku.penerima.hapus')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/{id}/penerima-master', 'penerimaMasterSimpan')->name('bku.penerima-master.simpan')
      ->middleware('role:Admin|Operator Sekolah');
    Route::put('/bku/{id}/penerima-master/{masterId}', 'penerimaMasterUbah')->name('bku.penerima-master.ubah')
      ->middleware('role:Admin|Operator Sekolah');
    Route::delete('/bku/{id}/penerima-master/{masterId}', 'penerimaMasterHapus')->name('bku.penerima-master.hapus')
      ->middleware('role:Admin|Operator Sekolah');
    // Meta grup SPJ — saklar status (belum/selesai)
    // & nomor VA SIPLah (manual) per grup transaksi.
    // Bendahara ikut boleh memanggil endpoint ini,
    // tetapi di controller ia dibatasi hanya mengubah
    // status (nomor VA, kategori & nama tampilan tidak
    // diterapkan untuknya).
    Route::put('/bku/{id}/penerima-grup', 'penerimaGrupUbah')->name('bku.penerima.grup')
      ->middleware('role:Admin|Operator Sekolah|Bendahara');
    // SPJ Grup Manual — gabung beberapa nota / pecah satu
    // nota menjadi grup SPJ bernama alias (lapisan SPJ,
    // data ARKAS tidak diubah). Isi grup dibaca mengikuti
    // akses BKU; penulisan hanya Admin & Operator Sekolah.
    Route::get('/bku/{id}/grup-anggota', 'grupAnggota')->name('bku.grup-anggota')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    // Nama uraian BKU per baris: nama khusus satu item
    // belanja yang diedit dari BKU (hanya berlaku untuk
    // item itu sendiri) — penulisan hanya Admin &
    // Operator Sekolah, sejajar alias.
    Route::put('/bku/{id}/nama-baris', 'namaBarisUbah')->name('bku.nama-baris')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/grup-manual/gabung', 'grupManualGabung')->name('bku.grup-manual.gabung')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/grup-manual/pecah', 'grupManualPecah')->name('bku.grup-manual.pecah')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/grup-manual/batal', 'grupManualBatal')->name('bku.grup-manual.batal')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/bku/grup-manual/{grupId}/nama', 'grupManualUbahNama')->name('bku.grup-manual.nama')
      ->middleware('role:Admin|Operator Sekolah');
    Route::get('/perbandingan', 'perbandingan')->name('perbandingan')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::get('/referensi/barang', 'referensiBarang')->name('referensi.barang')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::get('/referensi/kegiatan', 'referensiKegiatan')->name('referensi.kegiatan')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    Route::get('/referensi/rekening', 'referensiRekening')->name('referensi.rekening')
      ->middleware('role:Admin|Operator Sekolah|Kepala Sekolah|Bendahara');
    // Nama alias (Fase 1.7) — satu-satunya penulisan Kelola
    // BOS; lapisan kerja pengguna, bukan data cermin ARKAS.
    // Kepala Sekolah & Bendahara hanya membaca, jadi tidak ikut di sini.
    Route::post('/alias', 'simpanAlias')->name('alias.simpan')
      ->middleware('role:Admin|Operator Sekolah');
    // Token sinkron (Fase 1.12) — pembuatan token agen
    // dari web; penulisan lapisan kerja kedua. Kepala
    // Sekolah & Bendahara tidak mengelola kunci akses,
    // jadi halaman maupun aksinya tertutup untuk mereka.
    Route::get('/token', 'token')->name('token')
      ->middleware('role:Admin|Operator Sekolah');
    Route::post('/token/buat', 'tokenBuat')->name('token.buat')
      ->middleware('role:Admin|Operator Sekolah');
  });

/*
|--------------------------------------------------------------------------
| SURAT PINDAH SISWA (anak modul Surat Keluar, fitur sama)
|--------------------------------------------------------------------------
| Surat Keterangan Pindah untuk siswa berstatus mutasi.
| Nomornya dari penghitung Surat Keluar, agendanya tercatat
| di buku agenda yang sama (sumber 'surat-pindah'). Awalan
| rute 'surat-keluar' membuatnya ikut terkecuali dari kunci
| semester global, sama seperti modul induknya.
*/
Route::controller(SuratPindahController::class)
  ->prefix('surat-keluar/pindah')
  ->name('surat-keluar.pindah.')
  ->middleware(['auth:sanctum', 'fitur:surat-keluar'])
  ->group(function () {
    Route::get('/baru/{registrasi}', 'create')->name('create')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::post('/baru/{registrasi}', 'store')->name('store')
      ->middleware('role:Operator Sekolah|Tata Usaha');
    Route::get('/{surat}/cetak', 'cetak')->name('cetak')
      ->middleware('role:Admin|Operator Sekolah|Tata Usaha|Kepala Sekolah');
  });



Route::middleware(['auth'])->prefix('learning-modules')->name('learning-modules.')->group(function () {
  Route::get('/', [LearningModuleController::class, 'index'])->name('index');
  Route::get('/create', [LearningModuleController::class, 'create'])->name('create');
  Route::get('/data', [LearningModuleController::class, 'data'])->name('data');
  Route::post('/', [LearningModuleController::class, 'store'])->name('store');
  Route::get('/{learningModule}', [LearningModuleController::class, 'show'])->name('show');
  Route::post('/{learningModule}/retry', [LearningModuleController::class, 'retry'])->name('retry');
  // routes/web.php — dalam group learning-modules.
  Route::get('/{learningModule}/export', [LearningModuleController::class, 'export'])->name('export');
});


/*
|--------------------------------------------------------------------------
| FORMULIR K3S (fitur: formulir) — pulau Inertia/Vue
|--------------------------------------------------------------------------
| Batch 1 menyediakan halaman pembuka; Batch 2 menambahkan route
| builder (kelola form) khusus Admin di bawah ini.
*/
Route::controller(FormulirController::class)
  ->prefix('formulir')
  ->name('formulir.')
  ->middleware(['auth:sanctum', 'fitur:formulir'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
  });

Route::controller(FormulirBuilderController::class)
  ->prefix('formulir/kelola')
  ->name('formulir.kelola.')
  ->middleware(['auth:sanctum', 'fitur:formulir', 'role:Admin'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/buat', 'create')->name('create');
    Route::post('/', 'store')->name('store');
    Route::get('/{formulir}/edit', 'edit')->name('edit');
    Route::put('/{formulir}', 'update')->name('update');
    Route::delete('/{formulir}', 'destroy')->name('destroy');
    Route::put('/{formulir}/struktur', 'updateStructure')->name('structure');
  });

/*
|--------------------------------------------------------------------------
| FORMULIR K3S — jawaban & ekspor (Batch 4, khusus Admin)
|--------------------------------------------------------------------------
| Daftar/detail jawaban, ekspor Excel, ekspor berkas (ZIP), dan
| pemantauan per sekolah. Route ekspor didaftarkan sebelum route
| detail {submission} agar tidak tertangkap sebagai id jawaban.
*/
Route::controller(FormulirSubmissionController::class)
  ->prefix('formulir/kelola')
  ->name('formulir.kelola.')
  ->middleware(['auth:sanctum', 'fitur:formulir', 'role:Admin'])
  ->group(function () {
    Route::get('/{formulir}/jawaban', 'index')->name('submissions.index');
    Route::get('/{formulir}/jawaban/ekspor', 'exportExcel')->name('submissions.export');
    Route::get('/{formulir}/jawaban/ekspor-berkas', 'exportFiles')->name('submissions.exportFiles');
    Route::get('/{formulir}/jawaban/{submission}', 'show')->name('submissions.show');
  });

/*
|--------------------------------------------------------------------------
| FORMULIR K3S — halaman pengisian publik (Batch 3)
|--------------------------------------------------------------------------
| Route ini SENGAJA tanpa middleware auth/fitur: akses diatur oleh
| status form, periode, require_login, dan target role di dalam
| FormulirPublikController (persis pola form-k3s). Parameter anak
| memakai nama {submission} agar binding berlingkup (scopeBindings)
| memakai relasi submissions() pada model Formulir — nama relasi
| diturunkan Laravel dari nama parameter (Str::plural).
*/
Route::controller(FormulirPublikController::class)
  ->prefix('form')
  ->name('formulir.publik.')
  ->group(function () {
    Route::get('/{formulir:slug}', 'show')->name('show');
    Route::get('/{formulir:slug}/isi', 'create')->name('isi');
    Route::post('/{formulir:slug}', 'store')->name('simpan');

    Route::scopeBindings()->group(function () {
      // Pengguna login
      Route::get('/{formulir:slug}/jawaban/{submission}', 'edit')->name('edit');
      Route::put('/{formulir:slug}/jawaban/{submission}', 'update')->name('update');
      Route::get('/{formulir:slug}/jawaban/{submission}/lihat', 'review')
        ->middleware('auth')
        ->name('review');

      // Tamu (signed URL)
      Route::middleware('signed')->group(function () {
        Route::get('/{formulir:slug}/jawaban/{submission}/tamu', 'editGuest')->name('tamu.edit');
        Route::put('/{formulir:slug}/jawaban/{submission}/tamu', 'updateGuest')->name('tamu.update');
        Route::get('/{formulir:slug}/jawaban/{submission}/tamu/lihat', 'reviewGuest')->name('tamu.review');
      });
    });
  });
