<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\PresensiApiController;
use App\Http\Controllers\Api\SiswaApiController;
use App\Http\Controllers\ValidasiKelulusan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
  return $request->user();
})->middleware('auth:sanctum');

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
});
