<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DhSiswaController;
use App\Http\Controllers\KalenderPendidikanController;
use App\Http\Controllers\KartuPelajarController;
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
use App\Http\Controllers\SekolahProfilController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\SkhuController;
use App\Http\Controllers\SklController;
use App\Http\Controllers\SklKomponenController;
use App\Http\Controllers\SklKonfigurasiController;
use App\Http\Controllers\SklKomponenSubController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ValidasiKelulusan;
use App\Http\Controllers\WaliKelasController;
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

Route::get('/', fn() => redirect()->route('home'));
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
Route::controller(PtkController::class)
  ->prefix('ptk')
  ->name('ptk.')
  ->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah'])
  ->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/data', 'data')->name('data');
    Route::get('/{id}/edit', 'edit')->name('edit');
    Route::post('/', 'store')->name('store');
    Route::put('/{id}', 'update')->name('update');
    Route::delete('/{id}', 'destroy')->name('destroy');
    Route::post('/{id}/generate-akun', 'generateAkun')->name('generate-akun');
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
Route::prefix('presensi')->name('presensi.')->middleware(['auth:sanctum', 'role:Admin|Operator Sekolah|Guru', 'fitur:presensi'])->group(function () {
  Route::get('/', [PresensiController::class, 'index'])->name('index');
  Route::get('/ajax-siswa', [PresensiController::class, 'ajaxSiswa'])->name('ajaxSiswa');
  Route::post('/store', [PresensiController::class, 'store'])->name('store');
});

Route::middleware(['auth:sanctum', 'role:Operator Sekolah'])->group(function () {
  Route::get('/periodic/copy-form', [\App\Http\Controllers\DataPeriodikController::class, 'showCopyForm'])->name('periodic.copy-form');
  Route::post('/periodic/copy', [\App\Http\Controllers\DataPeriodikController::class, 'copyData'])->name('periodic.copy');
});

Route::get('/sekolah-belum-aktif', function () {
  return view('errors.sekolah-belum-aktif');
})->middleware('auth:sanctum')->name('sekolah.belum-aktif');



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
