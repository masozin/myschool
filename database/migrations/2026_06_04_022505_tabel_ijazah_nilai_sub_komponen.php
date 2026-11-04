<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Nilai aktual per sub-komponen per baris IjazahNilaiKomponen.
   *
   * Alur kalkulasi:
   *   1. Operator isi nilai per sub-komponen (misal S7=85, S8=88, S9=80, S10=90, S11=87)
   *   2. Sistem hitung rata-rata → simpan ke tabel_ijazah_nilai_komponen.nilai  (86)
   *   3. IjazahNilai::hitungNilaiAkhir() kalkulasi nilai_akhir dari semua komponen berbobot
   */
  public function up(): void
  {
    Schema::create('tabel_ijazah_nilai_sub_komponen', function (Blueprint $table) {
      $table->id();

      // FK ke baris nilai komponen (ijazah_id + komponen_id sudah ter-resolve di sini)
      $table->foreignId('ijazah_nilai_komponen_id')
        ->constrained('tabel_ijazah_nilai_komponen')
        ->cascadeOnDelete();

      $table->foreignId('sub_komponen_id')
        ->constrained('tabel_ijazah_sub_komponen')
        ->cascadeOnDelete();

      // Nilai per sub-komponen (0–100)
      $table->decimal('nilai', 5, 2)->nullable();

      $table->timestamps();

      $table->unique(['ijazah_nilai_komponen_id', 'sub_komponen_id'], 'unique_nilai_sub');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_ijazah_nilai_sub_komponen');
  }
};
