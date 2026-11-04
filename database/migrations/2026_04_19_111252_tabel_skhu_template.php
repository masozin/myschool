<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_skhu_template', function (Blueprint $table) {
      $table->id();
      $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK']);
      $table->foreignId('semester_id')->constrained('tabel_semester')->cascadeOnDelete();
      $table->foreignId('kurikulum_id')->constrained('tabel_kurikulum')->cascadeOnDelete();               // '2024/2025'
      // Path relatif dari resources/skhu-templates/
      // Contoh: 'sd/2024-2025' → resources/skhu-templates/sd/2024-2025.blade.php
      $table->string('nama_file');
      $table->string('keterangan')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();

      $table->unique(['jenjang', 'semester_id']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skhu_template');
  }
};
