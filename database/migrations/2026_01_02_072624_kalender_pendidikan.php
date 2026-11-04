<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('kalender_pendidikan', function (Blueprint $table) {
      $table->id();
      $table->foreignId('semester_id')->constrained('tabel_semester')->cascadeOnDelete();
      $table->string('jenis'); //[minggu, kbm, asesmen, orientasi siswa, libnas, libur ahir semseter, lainnya] untuk pengelompokan dan pewarnaan
      $table->boolean('is_libur')->default(false);
      $table->date('tanggal_mulai');
      $table->date('tanggal_selesai')->nullable();
      $table->string('kegiatan')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('kalender_pendidikan');
  }
};
