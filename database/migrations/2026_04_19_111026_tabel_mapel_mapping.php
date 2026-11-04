<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_mapel_mapping', function (Blueprint $table) {
      $table->id();
      // ── Referensi ke master mapel ─────────────────────────────────
      $table->foreignId('mapel_master_id')
        ->constrained('tabel_mapel_master')
        ->cascadeOnDelete()
        ->comment('FK ke master mapel');

      // ── Konteks akademik ──────────────────────────────────────────
      $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK']);
      $table->unsignedTinyInteger('tingkat')->comment('Kelas: 1–12');
      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      // ── Jenis & kepemilikan ───────────────────────────────────────
      // pokok    → berlaku semua sekolah (sekolah_id NULL)
      $table->enum('jenis', ['pokok', 'tambahan'])->default('pokok');

      // hanya untuk mapel sekolah tertentu jika mapel_master_id = NULL
      $table->foreignId('sekolah_id')
        ->nullable()
        ->constrained('tabel_sekolah')
        ->cascadeOnDelete();

      $table->unsignedTinyInteger('urutan')->default(1);
      $table->timestamps();

      // ── Index ─────────────────────────────────────────────────────
      $table->index(['jenjang', 'tingkat'], 'idx_konteks');
      $table->index(['jenis', 'sekolah_id'], 'idx_jenis_sekolah');

      $table->unique(
        ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_id'],
        'uniq_mapel_mapping'
      );
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_mapel_mapping');
  }
};
