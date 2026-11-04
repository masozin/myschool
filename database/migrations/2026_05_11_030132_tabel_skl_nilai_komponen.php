<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Detail nilai per komponen per mapel (hanya digunakan pada mode 'manual').
   *
   * Contoh data untuk 1 mapel Matematika:
   *   komponen_id=1 (rapor_s7_s11, bobot 40%), nilai=85  → kontribusi = 34
   *   komponen_id=2 (nilai_ujian,   bobot 40%), nilai=90  → kontribusi = 36
   *   komponen_id=3 (nilai_harian,  bobot 20%), nilai=88  → kontribusi = 17.6
   *   ─────────────────────────────────────────────────────
   *   nilai_akhir di tabel_skl_nilai = 87.6
   */
  public function up(): void
  {
    Schema::create('tabel_skl_nilai_komponen', function (Blueprint $table) {
      $table->id();

      // FK ke baris di tabel_skl_nilai (bukan ke skl langsung)
      $table->foreignId('skl_nilai_id')
        ->constrained('tabel_skl_nilai')
        ->cascadeOnDelete();

      // FK ke definisi komponen (rapor, ujian, harian, dsb)
      $table->foreignId('komponen_id')
        ->constrained('tabel_skl_komponen')
        ->cascadeOnDelete();

      // Nilai mentah untuk komponen ini (0-100)
      $table->decimal('nilai', 5, 2)->nullable();

      $table->timestamps();

      $table->unique(['skl_nilai_id', 'komponen_id'], 'unique_nilai_komponen');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_nilai_komponen');
  }
};
