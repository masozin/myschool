<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * NIS adalah nomor induk lokal: unik per sekolah, bukan global.
   * Sebelumnya keunikan ini hanya dijaga validasi aplikasi
   * (Rule::unique ber-where sekolah_id di SiswaController),
   * sekarang ditegakkan database juga.
   *
   * Kolom nis nullable — di MySQL, baris dengan nis NULL tidak
   * dianggap duplikat satu sama lain, jadi siswa tanpa NIS aman.
   *
   * CATATAN: migration ini akan gagal jika sudah ada pasangan
   * (sekolah_id, nis) yang duplikat. Bersihkan dulu sebelum migrate.
   */
  public function up(): void
  {
    Schema::table('tabel_siswa_registrasi', function (Blueprint $table) {
      $table->unique(['sekolah_id', 'nis'], 'registrasi_sekolah_nis_unique');
    });
  }

  public function down(): void
  {
    Schema::table('tabel_siswa_registrasi', function (Blueprint $table) {
      $table->dropUnique('registrasi_sekolah_nis_unique');
    });
  }
};
