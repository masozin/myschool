<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — penanda selesai per penerima:
 * kolom selesai di bos_spj_penerima menandai penerima
 * yang pembayaran SPJ-nya sudah tuntas. Status tampil
 * satu grup diturunkan dari penanda ini bila grup punya
 * penerima (semua selesai -> selesai, sebagian ->
 * selesai sebagian, belum ada -> belum); grup tanpa
 * penerima tetap mengikuti status meta bos_spj_grup.
 * Lihat KelolaBosController::statusTampilGrup.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_penerima') && !Schema::hasColumn('bos_spj_penerima', 'selesai')) {
      Schema::table('bos_spj_penerima', function (Blueprint $table) {
        $table->boolean('selesai')->default(false);
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('bos_spj_penerima') && Schema::hasColumn('bos_spj_penerima', 'selesai')) {
      Schema::table('bos_spj_penerima', function (Blueprint $table) {
        $table->dropColumn('selesai');
      });
    }
  }
};
