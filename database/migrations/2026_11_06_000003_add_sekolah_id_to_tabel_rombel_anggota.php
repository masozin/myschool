<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Sekolah keanggotaan dibuat eksplisit per baris (nilainya selalu
   * sekolah pemilik rombelnya), dan kekekalan satu baris per siswa
   * per semester dipersempit menjadi per SEKOLAH:
   * unique(siswa_id, semester_id, sekolah_id).
   *
   * Semester bersifat global untuk semua sekolah, jadi unique
   * sebelumnya — (siswa_id, semester_id) — menghalangi mutasi
   * antar sekolah di tengah semester yang sama: siswa yang pindah
   * tidak bisa dimasukkan ke rombel sekolah barunya karena baris
   * sekolah lamanya memakai semester yang sama.
   */
  public function up(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->foreignId('sekolah_id')->nullable()->after('semester_id')
        ->constrained('tabel_sekolah');
    });

    // Isi dari rombel masing-masing baris
    DB::statement(
      'UPDATE tabel_rombel_anggota a
       JOIN tabel_rombel r ON r.id = a.rombel_id
       SET a.sekolah_id = r.sekolah_id'
    );

    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
      $table->dropUnique(['siswa_id', 'semester_id']);
      $table->unique(['siswa_id', 'semester_id', 'sekolah_id']);
    });
  }

  public function down(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->dropUnique(['siswa_id', 'semester_id', 'sekolah_id']);
      $table->unique(['siswa_id', 'semester_id']);
      $table->dropForeign(['sekolah_id']);
      $table->dropColumn('sekolah_id');
    });
  }
};
