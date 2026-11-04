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
    Schema::create('learning_subjects', function (Blueprint $table) {
      $table->id();
      $table->foreignId('grade_id')->constrained('learning_grades')->onDelete('cascade');
      $table->string('name'); // Contoh: "IPAS"
      $table->string('icon')->nullable(); // Contoh: "bx-atom"
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('learning_subjects');
  }
};
