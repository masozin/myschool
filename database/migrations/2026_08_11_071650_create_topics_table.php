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
    Schema::create('learning_topics', function (Blueprint $table) {
      $table->id();

      $table->unsignedBigInteger('mapel_master_id')->nullable();
      $table->unsignedInteger('kelas')->nullable(); // kelas spesifik yang dipilih guru
      $table->unsignedBigInteger('sekolah_id')->nullable();   // jejak sekolah pembuat
      $table->unsignedBigInteger('ptk_id')->nullable();  // jejak guru/pembuat modul

      $table->foreign('mapel_master_id')
        ->references('id')->on('tabel_mapel_master')
        ->nullOnDelete(); // kalau mapel induk dihapus, modul tidak ikut terhapus, cuma jadi null

      $table->string('title'); // Contoh: "Sistem Pernapasan Manusia"
      $table->text('description')->nullable();
      $table->integer('order')->default(1);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('learning_topics');
  }
};
