<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up()
  {
    Schema::create('tabel_fitur_sekolah', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->foreignId('fitur_id')->constrained('tabel_fitur')->cascadeOnDelete();
      $table->boolean('aktif')->default(true);
      $table->timestamps();
      $table->unique(['sekolah_id', 'fitur_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_fitur_sekolah');
  }
};
