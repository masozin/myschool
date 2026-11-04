<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Header dokumen ijazah per siswa.
   * Satu siswa, satu ijazah (per sekolah/semester kelulusan).
   */
  public function up(): void
  {
    Schema::create('tabel_ijazah', function (Blueprint $table) {
      $table->id();

      $table->foreignId('siswa_id')
        ->constrained('tabel_siswa')
        ->cascadeOnDelete();

      $table->foreignId('sekolah_id')
        ->constrained('tabel_sekolah')
        ->cascadeOnDelete();

      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      // Nomor seri ijazah resmi
      $table->string('nomor_ijazah', 50)->nullable();

      // Tanggal penerbitan ijazah
      $table->date('tanggal_terbit')->nullable();

      /**
       * Mode input nilai:
       *   'jadi'   = nilai akhir langsung diinput (satu angka per mapel)
       *   'manual' = nilai dihitung dari komponen berbobot
       */
      $table->enum('mode_input', ['jadi', 'manual'])->default('jadi');

      /**
       * Status alur dokumen:
       *   draft      = baru dibuat, belum lengkap
       *   pengajuan  = operator submit, menunggu persetujuan
       *   disetujui  = kepala sekolah/admin acc
       *   ditolak    = dikembalikan untuk diperbaiki
       */
      $table->enum('status', ['draft', 'pengajuan', 'disetujui', 'ditolak'])
        ->default('draft');

      // Catatan penolakan jika status = ditolak
      $table->text('catatan')->nullable();

      // Timestamp saat digenerate/dicetak
      $table->timestamp('generated_at')->nullable();

      $table->timestamps();
      $table->softDeletes();

      // Satu siswa hanya punya satu ijazah per sekolah
      $table->unique(['siswa_id', 'sekolah_id'], 'unique_ijazah_siswa');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_ijazah');
  }
};
