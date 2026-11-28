<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — nama uraian BKU per baris.
 *
 * Tabel bos_spj_nama_baris menyimpan nama khusus SATU
 * baris BKU (satu item belanja) yang diedit dari BKU:
 * hanya berlaku untuk baris itu sendiri, tidak untuk
 * baris lain beruraian sama. Ini lapisan nama paling
 * spesifik, di atas alias teks (per teks uraian, dari
 * Kertas Kerja) dan nama tampilan grup (per grup /
 * nota, untuk uraian berisi beberapa item). Karena
 * melekat pada barisnya, nama ini ikut terbawa apa pun
 * grupnya (termasuk sesudah gabung / pecah nota).
 * Ketiadaan baris berarti baris tersebut memakai
 * alias teks / uraian asli ARKAS.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_spj_nama_baris')) {
      Schema::create('bos_spj_nama_baris', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')
          ->constrained('tabel_sekolah')
          ->cascadeOnDelete();
        $table->foreignId('bos_kas_umum_id')
          ->constrained('bos_kas_umum')
          ->cascadeOnDelete();
        $table->string('nama');
        $table->timestamps();
        $table->unique(['sekolah_id', 'bos_kas_umum_id']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_spj_nama_baris');
  }
};
