<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    // ── Tabel SKHU Header (satu record per siswa per tahun pelajaran) ──
    Schema::create('tabel_skhu', function (Blueprint $table) {
      $table->id();
      $table->foreignId('siswa_id')
        ->constrained('tabel_siswa')
        ->cascadeOnDelete();
      $table->foreignId('sekolah_id')
        ->constrained('tabel_sekolah')
        ->restrictOnDelete();
      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->restrictOnDelete();
      $table->string('nomor_surat')->nullable();       // input manual
      $table->string('nomor_ujian')->nullable();       // input manual
      $table->date('tanggal_mulai_ujian')->nullable();
      $table->date('tanggal_selesai_ujian')->nullable();
      $table->date('tanggal_terbit')->nullable();
      $table->enum('status', ['draft', 'final'])->default('draft');
      $table->timestamp('generated_at')->nullable();   // waktu terakhir PDF digenerate
      $table->timestamps();

      // Satu siswa hanya punya satu SKHU per semester
      $table->unique(['siswa_id', 'semester_id']);
    });

    // ── Tabel Nilai per Mata Pelajaran ──────────────────────────────────
    Schema::create('tabel_skhu_nilai', function (Blueprint $table) {
      $table->id();
      $table->foreignId('skhu_id')
        ->constrained('tabel_skhu')
        ->restrictOnDelete();
      $table->foreignId('mapel_mapping_id')
        ->constrained('tabel_mapel_mapping')
        ->restrictOnDelete();
      $table->decimal('nilai', 5, 2)->default(0);
      $table->timestamps();

      $table->unique(['skhu_id', 'mapel_mapping_id'], 'idx_unique_nilai_mapel');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skhu_nilai');
    Schema::dropIfExists('tabel_skhu');
  }
};
