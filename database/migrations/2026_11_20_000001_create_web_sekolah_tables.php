<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Web Sekolah (landing page publik per sekolah).
   *
   * Pengaturan web SENGAJA tidak ditaruh di tabel_sekolah_detail:
   * detail itu per-semester, sedangkan visi/misi/sambutan/foto hero
   * tidak berganti tiap semester. Satu baris per sekolah.
   * Nama/alamat/kontak/kepala sekolah tetap dibaca dari
   * tabel_sekolah_detail terbaru — satu sumber kebenaran.
   */
  public function up(): void
  {
    Schema::create('tabel_web_sekolah', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->string('slug')->unique();
      $table->boolean('is_aktif')->default(true);
      $table->string('tagline')->nullable();
      $table->text('visi')->nullable();
      $table->text('misi')->nullable();
      $table->text('sambutan')->nullable();
      $table->string('foto_hero')->nullable();
      $table->string('akreditasi', 10)->nullable();
      $table->unsignedSmallInteger('tahun_berdiri')->nullable();
      $table->timestamps();

      $table->unique('sekolah_id');
    });

    Schema::create('tabel_web_berita', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
      $table->string('judul');
      $table->string('slug');
      $table->longText('isi');
      $table->string('foto')->nullable();
      $table->string('status', 10)->default('draf'); // draf | terbit
      $table->timestamp('diterbitkan_pada')->nullable();
      $table->timestamps();

      $table->unique(['sekolah_id', 'slug']);
      $table->index(['sekolah_id', 'status']);
    });

    Schema::create('tabel_web_galeri', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->string('judul')->nullable();
      $table->string('keterangan')->nullable();
      $table->string('foto');
      $table->unsignedInteger('urutan')->default(0);
      $table->timestamps();

      $table->index(['sekolah_id', 'urutan']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_web_galeri');
    Schema::dropIfExists('tabel_web_berita');
    Schema::dropIfExists('tabel_web_sekolah');
  }
};
