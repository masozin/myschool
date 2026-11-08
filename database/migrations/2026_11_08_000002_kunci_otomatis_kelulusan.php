<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda buka-kunci manual untuk kunci otomatis kelulusan.
 *
 * Angkatan kelulusan terkunci otomatis begitu musim kelulusan
 * berikutnya dimulai (lihat App\Support\KunciKelulusan). Admin
 * dapat membuka kunci satu angkatan bila memang diperlukan —
 * penanda itu disimpan per sekolah + semester pada tabel
 * konfigurasi SKHU dan SKL.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_skhu_konfigurasi', 'kunci_dibuka')) {
      Schema::table('tabel_skhu_konfigurasi', function (Blueprint $table) {
        $table->boolean('kunci_dibuka')->default(false);
      });
    }

    if (!Schema::hasColumn('tabel_skl_konfigurasi', 'kunci_dibuka')) {
      Schema::table('tabel_skl_konfigurasi', function (Blueprint $table) {
        $table->boolean('kunci_dibuka')->default(false);
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_skhu_konfigurasi', 'kunci_dibuka')) {
      Schema::table('tabel_skhu_konfigurasi', function (Blueprint $table) {
        $table->dropColumn('kunci_dibuka');
      });
    }

    if (Schema::hasColumn('tabel_skl_konfigurasi', 'kunci_dibuka')) {
      Schema::table('tabel_skl_konfigurasi', function (Blueprint $table) {
        $table->dropColumn('kunci_dibuka');
      });
    }
  }
};
