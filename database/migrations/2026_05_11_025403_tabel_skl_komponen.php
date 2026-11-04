<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Konfigurasi komponen/sumber nilai SKL per jenjang per semester.
   * Dibuat oleh Admin, dipakai saat input manual.
   *
   * Contoh baris:
   * semester_id=1, jenjang=SD, kode=rapor_s1_s5,
   * label="Nilai Rapor Semester 1-5", bobot=40
   */
  public function up(): void
  {
    Schema::create('tabel_skl_komponen', function (Blueprint $table) {
      $table->id();

      // Mengganti tahun_pelajaran dengan semester_id sesuai dengan relasi di Model
      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      $table->string('jenjang', 10);
      $table->string('kode', 50);
      $table->string('label', 100);
      $table->unsignedTinyInteger('bobot'); // 0-100
      $table->string('keterangan', 255)->nullable();
      $table->unsignedTinyInteger('urutan')->default(0);

      $table->timestamps();
      $table->softDeletes(); // Sesuai dengan trait SoftDeletes di Model

      // Satu kode komponen unik per semester per jenjang
      $table->unique(['semester_id', 'jenjang', 'kode'], 'unique_komponen_skl');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_komponen');
  }
};
