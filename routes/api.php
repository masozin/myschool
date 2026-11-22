<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BosSinkronController;
use App\Http\Controllers\Api\InfoApiController;
use App\Http\Controllers\Api\JadwalApiController;
use App\Http\Controllers\Api\PresensiApiController;
use App\Http\Controllers\Api\SiswaApiController;
use App\Http\Controllers\ValidasiKelulusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| SINKRON KELOLA BOS — agen di laptop ARKAS mengirim cermin
| data ARKAS per tabel. Otentikasi BUKAN Sanctum pengguna,
| melainkan token sinkron per sekolah (middleware bos.token);
| sekolah tujuan ditentukan token, bukan isi kiriman.
|--------------------------------------------------------------------------
*/
Route::middleware('bos.token')
  ->prefix('bos/sinkron')
  ->group(function () {
    Route::get('/cek', [BosSinkronController::class, 'cek']);
    Route::post('/{tabel}', [BosSinkronController::class, 'simpan'])
      ->where('tabel', 'kas-umum|kas-nota|rapbs|rapbs-periode|anggaran|ref-rekening|ref-kode|ref-bku|ref-acuan-barang|ref-satuan|ref-periode|penjab');
  });

Route::get('/validasi-skhu/{nisn}', [ValidasiKelulusan::class, 'validasiSkhu'])
  ->name('api.skhu')
  ->where('nisn', '[0-9]{10}');

Route::get('/validasi-skl/{nisn}', [ValidasiKelulusan::class, 'validasiSkl'])
  ->name('api.skl')
  ->where('nisn', '[0-9]{10}');

/*
|--------------------------------------------------------------------------
| API APLIKASI MOBILE (Flutter) — token Sanctum per perangkat.
| Aturan presensi memakai App\Support\PresensiLayanan yang sama
| dengan halaman web; data siswa dibatasi sekolah pengguna.
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/saya', [AuthApiController::class, 'saya']);
  Route::post('/logout', [AuthApiController::class, 'logout']);

  Route::get('/presensi/rombel-saya', [PresensiApiController::class, 'rombelSaya']);
  Route::get('/presensi/roster', [PresensiApiController::class, 'roster']);
  Route::post('/presensi', [PresensiApiController::class, 'simpan']);

  Route::get('/siswa', [SiswaApiController::class, 'daftar']);
  Route::get('/siswa/{id}', [SiswaApiController::class, 'detail'])->whereNumber('id');

  Route::get('/info-hari-ini', [InfoApiController::class, 'hariIni']);

  Route::get('/jadwal/hari-ini', [JadwalApiController::class, 'hariIni']);
  Route::get('/jadwal/saya', [JadwalApiController::class, 'jadwalSaya']);
  Route::get('/jadwal/rombel', [JadwalApiController::class, 'daftarRombel']);
  Route::get('/jadwal/rombel/{id}', [JadwalApiController::class, 'jadwalRombel'])->whereNumber('id');
});
