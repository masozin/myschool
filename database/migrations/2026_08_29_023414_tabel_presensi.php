<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_presensi', function (Blueprint $table) {
      $table->id();

      $table->foreignId('siswa_id')
        ->constrained('tabel_siswa')
        ->cascadeOnDelete();

      $table->foreignId('rombel_id')
        ->constrained('tabel_rombel')
        ->cascadeOnDelete();

      $table->foreignId('semester_id')
        ->constrained('tabel_semester')
        ->cascadeOnDelete();

      $table->date('tanggal');

      $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa'])
        ->default('alpa');

      $table->text('keterangan')->nullable();

      // guru/wali kelas yang menginput
      $table->foreignId('input_by')
        ->nullable()
        ->constrained('users')
        ->nullOnDelete();

      $table->timestamps();

      // 1 siswa hanya boleh punya 1 status presensi per hari
      $table->unique(['siswa_id', 'tanggal']);

      // mempercepat query rekap per rombel per tanggal
      $table->index(['rombel_id', 'tanggal']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_presensi');
  }
};
