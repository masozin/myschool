<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Konfigurasi ijazah per sekolah per semester.
   * Menggunakan semester_id (FK ke tabel_semester) agar konsisten
   * dengan pola yang sudah ada di aplikasi.
   *
   * Satu sekolah hanya boleh punya satu konfigurasi per semester.
   * Setelah ada ijazah yang dibuat, is_terkunci = true → tidak bisa diubah.
   */
  public function up(): void
  {
    Schema::create('tabel_ijazah_konfigurasi', function (Blueprint $table) {
      $table->id();

      $table->foreignId('sekolah_id')
        ->constrained('tabel_sekolah')
        ->cascadeOnDelete();

      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      /**
       * Mode input nilai ijazah berlaku untuk SEMUA siswa sekolah ini:
       *   'jadi'   = operator input nilai akhir langsung
       *   'manual' = nilai dihitung dari komponen berbobot
       */
      $table->enum('mode_input', ['jadi', 'manual'])->default('jadi');

      // Dikunci otomatis saat ijazah pertama dibuat
      $table->boolean('is_terkunci')->default(false);

      $table->timestamps();

      // Satu konfigurasi per sekolah per semester
      $table->unique(['sekolah_id', 'semester_id'], 'unique_konfigurasi_ijazah');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_ijazah_konfigurasi');
  }
};
