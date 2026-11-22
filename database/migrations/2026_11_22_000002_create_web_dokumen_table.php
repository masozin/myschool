<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen publik sekolah di landing page Web Sekolah: sertifikat
 * akreditasi, KOSP, RKT, RKJM, RKAS, dan sejenisnya. Berkas disimpan
 * di disk public dan DIALIRKAN lewat route (lihat/unduh) — bukan
 * asset('storage/...') — agar tidak tergantung symlink di hosting.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('tabel_web_dokumen')) {
      Schema::create('tabel_web_dokumen', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('judul');
        $table->string('kategori', 50)->default('lainnya');
        $table->unsignedSmallInteger('tahun')->nullable();
        $table->string('keterangan')->nullable();
        $table->string('berkas');
        $table->string('nama_berkas')->nullable();
        $table->unsignedBigInteger('ukuran')->nullable();
        $table->boolean('is_tampil')->default(true);
        $table->timestamps();

        $table->index(['sekolah_id', 'kategori']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_web_dokumen');
  }
};
