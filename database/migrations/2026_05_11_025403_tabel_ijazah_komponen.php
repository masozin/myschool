<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Konfigurasi komponen/sumber nilai ijazah per jenjang per tahun pelajaran.
   * Dibuat oleh Admin, dipakai saat input manual.
   *
   * Contoh baris:
   *   jenjang=SD, tahun_pelajaran=2025/2026, kode=rapor_s1_s5,
   *   label="Nilai Rapor Semester 1-5", bobot=40
   */
  public function up(): void
  {
    Schema::create('tabel_ijazah_komponen', function (Blueprint $table) {
      $table->id();
      $table->string('jenjang', 10);
      $table->string('tahun_pelajaran', 9);
      $table->string('kode', 50);
      $table->string('label', 100);
      $table->unsignedTinyInteger('bobot'); // 0-100
      $table->string('keterangan', 255)->nullable();
      $table->unsignedTinyInteger('urutan')->default(0);

      $table->timestamps();
      $table->softDeletes();

      // Satu kode komponen unik per jenjang per tahun pelajaran
      $table->unique(['jenjang', 'tahun_pelajaran', 'kode'], 'unique_komponen');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_ijazah_komponen');
  }
};
