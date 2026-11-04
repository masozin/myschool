<?php

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
