<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — nama tampilan per grup.
 *
 * bos_spj_grup: kolom nama (nullable) adalah nama
 * khusus SATU grup transaksi (per nota / nomor bukti)
 * yang menggantikan uraian tampilnya di BKU, kwitansi,
 * dan Tanda Terima — mis. dua nota beruraian sama
 * "Perjalanan Dinas" dibedakan menjadi "Perjalanan
 * Dinas A" dan "Perjalanan Dinas B". Berbeda dari
 * alias teks (bos_nama_alias) yang berlaku untuk
 * semua baris beruraian sama persis, dan dari nama
 * grup manual yang khusus grup gabung / pecah.
 * Kosong (null) berarti memakai uraian / alias biasa.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_grup')
      && !Schema::hasColumn('bos_spj_grup', 'nama')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->string('nama')->nullable();
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('bos_spj_grup')
      && Schema::hasColumn('bos_spj_grup', 'nama')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->dropColumn('nama');
      });
    }
  }
};
