<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('tabel_rombel_anggota', function (Blueprint $table) {
      $table->id();
      $table->foreignId('siswa_id')->constrained('tabel_siswa')->onDelete('cascade');
      $table->foreignId('rombel_id')->constrained('tabel_rombel')->restrictOnDelete();
      $table->foreignId('semester_id')->constrained('tabel_semester')->restrictOnDelete();
      $table->date('start_date');
      $table->date('end_date')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_rombel_anggota');
  }
};
