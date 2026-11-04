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
    Schema::create('tabel_siswa_registrasi', function (Blueprint $table) {
      $table->id();

      $table->foreignId('siswa_id')->constrained('tabel_siswa')->cascadeOnDelete();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->string('nis')->nullable();
      $table->string('jenis_pendaftaran')->nullable();
      $table->string('tingkat')->nullable();
      $table->date('tanggal_diterima')->nullable();
      $table->string('sekolah_asal')->nullable();
      $table->date('tanggal_keluar')->nullable();
      $table->text('alasan')->nullable();
      $table->string('sekolah_tujuan')->nullable();
      $table->string('status')->default('aktif');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_siswa_registrasi');
  }
};
