<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Nilai akhir skl per mapel.
   *
   * Mode 'jadi'  : nilai_akhir diisi langsung, kolom nilai_huruf opsional.
   * Mode 'manual': nilai_akhir = hasil kalkulasi dari tabel_skl_nilai_komponen,
   *                dihitung otomatis saat simpan.
   */
  public function up(): void
  {
    Schema::create('tabel_skl_nilai', function (Blueprint $table) {
      $table->id();

      $table->foreignId('skl_id')
        ->constrained('tabel_skl')
        ->cascadeOnDelete();

      // FK ke mapping mapel (jenjang + tingkat + semester + sekolah)
      $table->foreignId('mapel_mapping_id')
        ->constrained('tabel_mapel_mapping')
        ->cascadeOnDelete();

      // Nilai akhir yang tercetak di skl (hasil akhir, 0-100 atau 0.00-100.00)
      $table->decimal('nilai_akhir', 5, 2)->nullable();

      // Nilai dalam huruf opsional (misal: "Delapan Puluh Lima")
      $table->string('nilai_huruf', 100)->nullable();

      $table->timestamps();

      $table->unique(['skl_id', 'mapel_mapping_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_nilai');
  }
};
