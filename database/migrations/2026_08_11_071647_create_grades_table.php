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
    Schema::create('learning_grades', function (Blueprint $table) {
      $table->id();
      $table->string('name'); // Contoh: "Kelas 1", "Kelas 4", dsb.
      $table->string('level')->nullable(); // Opsional jika dibutuhkan model LearningGrade
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('learning_grades');
  }
};