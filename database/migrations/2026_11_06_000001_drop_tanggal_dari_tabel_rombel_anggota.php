<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Keanggotaan rombel tidak lagi memakai start_date/end_date: satu
   * baris keanggotaan berlaku untuk semester pada baris itu sendiri
   * (semester_id), sehingga tanggal mulai/selesai keanggotaan tidak
   * diperlukan. Hari efektif seorang siswa diatur oleh tanggal
   * registrasinya (tanggal_diterima / tanggal_keluar pada
   * tabel_siswa_registrasi), bukan oleh tanggal keanggotaan rombel.
   */
  public function up(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->dropColumn(['start_date', 'end_date']);
    });
  }

  public function down(): void
  {
    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->date('start_date')->nullable();
      $table->date('end_date')->nullable();
    });
  }
};
