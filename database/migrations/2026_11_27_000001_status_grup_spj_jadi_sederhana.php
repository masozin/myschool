<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (penyederhanaan) — status grup SPJ di
 * bos_spj_grup disederhanakan menjadi saklar dua nilai:
 * 'belum' dan 'selesai'.
 *
 * Baris yang terlanjur berstatus 'lunas' disatukan ke
 * 'selesai' agar tetap sah menurut konstanta
 * BosSpjGrup::STATUS yang baru.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_grup')) {
      DB::table('bos_spj_grup')
        ->where('status', 'lunas')
        ->update(['status' => 'selesai']);
    }
  }

  public function down(): void
  {
    // Tidak bisa dibalikkan: asal nilai 'lunas' dari
    // baris yang sudah menjadi 'selesai' tidak dapat
    // diketahui lagi.
  }
};
