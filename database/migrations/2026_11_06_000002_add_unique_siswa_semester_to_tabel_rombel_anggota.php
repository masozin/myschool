<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Satu siswa hanya boleh punya SATU baris keanggotaan per
   * semester. Sebelumnya kekekalan ini hanya dijaga logika
   * aplikasi, dan sisa data dari masa start/end date (baris lama
   * yang ditutup lalu baris baru dibuat) menyisakan pasangan
   * ganda pada semester yang sama — terlihat di riwayat rombel
   * siswa sebagai dua rombel dalam satu semester.
   *
   * Migrasi ini akan GAGAL bila masih ada pasangan
   * (siswa_id, semester_id) yang ganda: bersihkan dulu secara
   * sadar (sisakan satu baris per pasangan), baru migrate.
   */
  public function up(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->unique(['siswa_id', 'semester_id']);
    });
  }

  public function down(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->dropUnique(['siswa_id', 'semester_id']);
    });
  }
};
