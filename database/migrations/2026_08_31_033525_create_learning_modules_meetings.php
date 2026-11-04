<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('learning_module_meetings', function (Blueprint $table) {
      $table->id();
      $table->foreignId('learning_module_id')->constrained('learning_modules')->cascadeOnDelete();

      $table->unsignedTinyInteger('pertemuan_ke');
      $table->unsignedSmallInteger('jp')->default(0);

      $table->text('tujuan_pembelajaran')->nullable();
      $table->longText('kegiatan_pendahuluan')->nullable();
      $table->longText('kegiatan_inti')->nullable();
      $table->longText('kegiatan_penutup')->nullable();
      $table->text('asesmen')->nullable();       // formatif/sumatif untuk pertemuan ini
      $table->text('pengayaan_remedial')->nullable();
      $table->json('lampiran')->nullable();       // link LKPD, bahan bacaan, dsb

      $table->timestamps();

      $table->unique(['learning_module_id', 'pertemuan_ke']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('learning_module_meetings');
  }
};
