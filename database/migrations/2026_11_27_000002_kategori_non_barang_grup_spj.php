<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — kategori grup transaksi BKU:
 * non-barang (pembayaran ke orang: honor, transport,
 * insentif — sasaran dokumen Tanda Terima) vs barang.
 *
 * Kolom non_barang di bos_spj_grup menyimpan OVERRIDE
 * manual atas deteksi otomatis dari kode rekening
 * (lihat KelolaBosController::deteksiNonBarang):
 * - null  = ikut deteksi otomatis,
 * - true  = paksa non-barang,
 * - false = paksa barang.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_grup') && !Schema::hasColumn('bos_spj_grup', 'non_barang')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->boolean('non_barang')->nullable();
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('bos_spj_grup') && Schema::hasColumn('bos_spj_grup', 'non_barang')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->dropColumn('non_barang');
      });
    }
  }
};
