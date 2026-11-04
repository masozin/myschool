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
    Schema::create('tabel_rombel', function (Blueprint $table) {
      $table->id();
      $table->string('nama_rombel');
      $table->foreignId('kurikulum_id')->constrained('tabel_kurikulum')->restrictOnDelete();
      $table->unsignedTinyInteger('tingkat');
      $table->foreignId('semester_id')->constrained('tabel_semester')->restrictOnDelete();
      $table->foreignId('ptk_id')->nullable()->constrained('tabel_ptk')->nullOnDelete();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->restrictOnDelete();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_rombel');
  }
};
