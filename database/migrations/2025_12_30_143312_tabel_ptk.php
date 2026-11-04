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
    Schema::create('tabel_ptk', function (Blueprint $table) {
      $table->id(); // Tetap menggunakan UUID sesuai konsistensi
      $table->string('nama_ptk');
      $table->char('nip', 18)->unique()->nullable();
      $table->enum('jenis_kelamin', ['L', 'P']);
      $table->string('jabatan');
      $table->string('jenis_kepegawaian')->nullable(); //PNS /PPPK /Honorer
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->restrictOnDelete();
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // Menghubungkan ke tabel user bawaan Laravel
      $table->string('photo')->nullable();
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_ptk');
  }
};
