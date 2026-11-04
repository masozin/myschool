<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_sekolah_logo', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')
        ->constrained('tabel_sekolah')
        ->cascadeOnDelete();
      // ttd_ks, stempel, logo_sekolah, logo_kabupaten
      $table->string('tipe', 30);
      $table->string('path_file');
      $table->string('keterangan')->nullable();
      $table->timestamps();

      // Setiap sekolah hanya boleh punya satu aset per tipe
      $table->unique(['sekolah_id', 'tipe']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_sekolah_logo');
  }
};
