<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Konfigurasi SKL per sekolah per semester.
   * Menggunakan semester_id (FK ke tabel_semester) agar konsisten
   * dengan pola yang sudah ada di aplikasi.
   *
   * Satu sekolah hanya boleh punya satu konfigurasi per semester.
   * Setelah ada SKL yang dibuat, is_terkunci = true → tidak bisa diubah (mode_input).
   */
  public function up(): void
  {
    Schema::create('tabel_skl_konfigurasi', function (Blueprint $table) {
      $table->id();

      $table->foreignId('sekolah_id')
        ->constrained('tabel_sekolah')
        ->cascadeOnDelete();

      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      /**
       * Mode input nilai SKL berlaku untuk SEMUA siswa sekolah ini:
       * 'jadi'   = operator input nilai akhir langsung
       * 'manual' = nilai dihitung dari komponen berbobot
       */
      $table->enum('mode_input', ['jadi', 'manual'])->default('jadi');

      // Dikunci otomatis saat SKL pertama dibuat
      $table->boolean('is_terkunci')->default(false);

      // Kolom tambahan menyesuaikan atribut pada model SklKonfigurasi
      $table->string('tempat_terbit')->nullable()->comment('Kota/tempat penerbitan SKL');
      $table->date('tanggal_terbit')->nullable()->comment('Tanggal terbit default untuk SKL');
      $table->string('prefix_nomor')->nullable()->comment('Prefix nomor SKL, misal: "SKL"');

      $table->timestamps();

      // Satu konfigurasi per sekolah per semester
      $table->unique(['sekolah_id', 'semester_id'], 'unique_konfigurasi_skl');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_konfigurasi');
  }
};
