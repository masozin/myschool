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
    Schema::create('tabel_sekolah_detail', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->onDelete('cascade');
      $table->foreignId('semester_id')->constrained('tabel_semester')->onDelete('cascade');
      $table->string('nama_sekolah');
      $table->string('province_id', 2)->nullable();
      $table->string('regency_id', 5)->nullable();
      $table->string('district_id', 8)->nullable();
      $table->string('village_id', 13)->nullable();
      $table->text('alamat_sekolah')->nullable();
      $table->string('kepala_sekolah')->nullable();
      $table->char('nip', 18)->nullable();
      $table->json('libur_mingguan')->nullable();
      $table->string('no_telp')->nullable();
      $table->string('email')->nullable();
      $table->string('site')->nullable();
      $table->timestamps();

      $table->foreign('province_id')
        ->references('code')
        ->on('indonesia_provinces');

      $table->foreign('regency_id')
        ->references('code')
        ->on('indonesia_cities');

      $table->foreign('district_id')
        ->references('code')
        ->on('indonesia_districts');

      $table->foreign('village_id')
        ->references('code')
        ->on('indonesia_villages');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('tabel_sekolah_detail');
  }
};
