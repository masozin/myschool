<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tempat lahir PTK — pendamping tanggal_lahir yang sudah ada.
 * Selama ini tabel_ptk hanya menyimpan tanggal lahir, sedangkan
 * dokumen resmi (SK, ijazah, surat) selalu menulis tempat + tanggal.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_ptk', 'tempat_lahir')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->string('tempat_lahir', 100)->nullable();
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'tempat_lahir')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('tempat_lahir');
      });
    }
  }
};
