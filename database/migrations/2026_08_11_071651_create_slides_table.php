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
    Schema::create('learning_slides', function (Blueprint $table) {
      $table->id();
      $table->foreignId('topic_id')->constrained('learning_topics')->onDelete('cascade');
      $table->integer('slide_number');
      $table->string('title');
      $table->string('type');
      $table->json('content_data')->nullable();
      $table->longText('content_text')->nullable();
      $table->string('media_url')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('learning_slides');
  }
};
