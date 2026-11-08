<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Status kepegawaian PTK: aktif / tidak_aktif / pensiun beserta
 * tanggal keluar untuk yang sudah tidak bertugas. Selama ini
 * tabel PTK tidak memiliki penanda status sama sekali.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_ptk', 'status')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->string('status', 20)->default('aktif');
      });
    }

    if (!Schema::hasColumn('tabel_ptk', 'tanggal_keluar')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->date('tanggal_keluar')->nullable();
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'tanggal_keluar')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('tanggal_keluar');
      });
    }

    if (Schema::hasColumn('tabel_ptk', 'status')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('status');
      });
    }
  }
};
