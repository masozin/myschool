<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_kurikulum', function (Blueprint $table) {
      $table->id();
      $table->string('kode')->unique()->comment('Contoh: K13, K13-REV, MERDEKA');
      $table->string('nama')->comment('Nama lengkap kurikulum');
      $table->text('deskripsi')->nullable();
      $table->year('tahun_berlaku');
      $table->boolean('is_aktif')->default(true);
      $table->timestamps();
      $table->softDeletes();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_kurikulum');
  }
};
