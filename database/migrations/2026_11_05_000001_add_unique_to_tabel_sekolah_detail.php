<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Satu sekolah hanya boleh punya satu detail per semester.
   * Sebelumnya aturan ini hanya dijaga logika controller
   * (cari detail -> update / buat baru), sekarang ditegakkan database.
   *
   * CATATAN: migration ini akan gagal jika di tabel_sekolah_detail
   * sudah ada pasangan (sekolah_id, semester_id) yang duplikat.
   * Bersihkan duplikatnya dulu sebelum menjalankan migration.
   */
  public function up(): void
  {
    Schema::table('tabel_sekolah_detail', function (Blueprint $table) {
      $table->unique(['sekolah_id', 'semester_id'], 'sekolah_detail_sekolah_semester_unique');
    });
  }

  public function down(): void
  {
    Schema::table('tabel_sekolah_detail', function (Blueprint $table) {
      $table->dropUnique('sekolah_detail_sekolah_semester_unique');
    });
  }
};
