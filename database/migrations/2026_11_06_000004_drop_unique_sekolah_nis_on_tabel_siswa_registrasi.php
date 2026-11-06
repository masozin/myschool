<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Unique (sekolah_id, nis) di tabel_siswa_registrasi dilepas.
   *
   * Unique itu melarang satu hal yang sah: siswa yang pernah
   * terdaftar di sebuah sekolah, mutasi ke sekolah lain, lalu
   * KEMBALI ke sekolah asal dan memakai NIS lamanya di sana —
   * NIS itu masih tercatat di baris registrasi lamanya sendiri.
   * Database tidak bisa menyatakan "unik per sekolah, kecuali
   * untuk siswa yang sama", jadi penjagaannya dipindah penuh
   * ke validasi aplikasi: form tambah siswa menolak NIS yang
   * sudah dipakai di sekolah itu, dan mutasi masuk menolak NIS
   * milik siswa LAIN di sekolah tujuan.
   */
  public function up(): void
  {
    // Indeks biasa penopang foreign key sekolah_id disiapkan
    // lebih dulu — pelajaran dari error 1553: jangan membuang
    // indeks yang mungkin sedang menopang foreign key sebelum
    // ada penggantinya
    if (!Schema::hasIndex('tabel_siswa_registrasi', 'tabel_siswa_registrasi_sekolah_id_index')) {
      Schema::table('tabel_siswa_registrasi', function (Blueprint $table) {
        $table->index('sekolah_id');
      });
    }

    if (Schema::hasIndex('tabel_siswa_registrasi', 'tabel_siswa_registrasi_sekolah_id_nis_unique')) {
      Schema::table('tabel_siswa_registrasi', function (Blueprint $table) {
        $table->dropUnique(['sekolah_id', 'nis']);
      });
    }
  }

  public function down(): void
  {
    // Akan gagal bila saat itu sudah ada pasangan (sekolah_id,
    // nis) yang ganda — misalnya siswa yang kembali memakai NIS
    // lamanya. Memang disengaja: membersihkannya keputusan sadar.
    Schema::table('tabel_siswa_registrasi', function (Blueprint $table) {
      $table->unique(['sekolah_id', 'nis']);
    });
  }
};
