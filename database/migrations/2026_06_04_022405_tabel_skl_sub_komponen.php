<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Sub-komponen dari sebuah komponen nilai skl.
   * Nilai komponen induk = rata-rata dari semua sub-komponennya.
   *
   * Contoh:
   *   Komponen  : "Nilai Rapor Semester 7–11" (bobot 40%)
   *   Sub       : Semester 7, Semester 8, Semester 9, Semester 10, Semester 11
   *   Nilai induk = (S7 + S8 + S9 + S10 + S11) / 5   → lalu × 40% ke nilai akhir
   *
   * Jika komponen TIDAK punya sub → nilai diinput langsung ke tabel_skl_nilai_komponen.
   * Jika komponen PUNYA sub       → nilai di tabel_skl_nilai_komponen diisi otomatis
   *                                  dari rata-rata tabel_skl_nilai_sub_komponen.
   */
  public function up(): void
  {
    Schema::create('tabel_skl_sub_komponen', function (Blueprint $table) {
      $table->id();

      $table->foreignId('komponen_id')
        ->constrained('tabel_skl_komponen')
        ->cascadeOnDelete();

      // Label sub-komponen, misal: "Semester 7", "Semester 8"
      $table->string('label', 100);
      $table->string('keterangan', 100);

      // Urutan tampil
      $table->unsignedTinyInteger('urutan')->default(0);

      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_sub_komponen');
  }
};
