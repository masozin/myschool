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
    Schema::create('tabel_semester', function (Blueprint $table) {
      $table->id();
      $table->string('kode_semester')->unique(); // Contoh: 24251
      $table->string('semester');
      $table->string('tahun_pelajaran');
      $table->date('tanggal_mulai')->nullable();
      $table->date('tanggal_selesai')->nullable();
      $table->boolean('is_active')->default(false);
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_semester');
  }
};
