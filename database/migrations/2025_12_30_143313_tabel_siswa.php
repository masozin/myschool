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
    Schema::create('tabel_siswa', function (Blueprint $table) {
      $table->id();
      $table->string('nama_siswa');
      $table->char('nisn', 10)->unique();
      $table->enum('jenis_kelamin', ['L', 'P']);
      $table->string('tempat_lahir');
      $table->date('tgl_lahir');
      $table->char('nik', 16)->unique()->nullable();
      $table->string('agama');
      $table->text('alamat');
      $table->string('disabilitas')->default('Tidak ada');
      $table->string('nama_ayah')->nullable();
      $table->integer('tahun_ayah')->nullable();
      $table->string('nik_ayah')->nullable();
      $table->string('pekerjaan_ayah')->nullable();
      $table->string('nama_ibu')->nullable();
      $table->integer('tahun_ibu')->nullable();
      $table->string('nik_ibu')->nullable();
      $table->string('pekerjaan_ibu')->nullable();
      $table->string('no_hp')->nullable();
      $table->string('nama_wali')->nullable();
      $table->integer('tahun_wali')->nullable();
      $table->string('nik_wali')->nullable();
      $table->string('pekerjaan_wali')->nullable();
      $table->string('no_hp_wali')->nullable();
      $table->string('status')->default('aktif'); //'aktif', 'mutasi', 'lulus', 'meninggal
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
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
    Schema::dropIfExists('tabel_siswa');
  }
};
