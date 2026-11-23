<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Album kegiatan untuk galeri web sekolah: foto-foto galeri kini bisa
 * dikelompokkan per kegiatan (mis. "Upacara HUT RI 2026"). Satu tingkat
 * saja — album berisi foto, tanpa sub-album. Foto lama tidak disentuh:
 * kolom kegiatan_id nullable, foto tanpa album tampil sebagai
 * "Foto Lainnya" di web publik. Menghapus album TIDAK menghapus foto
 * (kegiatan_id menjadi null lewat nullOnDelete).
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_web_galeri_kegiatan', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->string('nama');
      $table->date('tanggal')->nullable();
      $table->text('keterangan')->nullable();
      $table->timestamps();

      $table->index(['sekolah_id', 'tanggal']);
    });

    Schema::table('tabel_web_galeri', function (Blueprint $table) {
      $table->foreignId('kegiatan_id')->nullable()->after('sekolah_id')
        ->constrained('tabel_web_galeri_kegiatan')->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('tabel_web_galeri', function (Blueprint $table) {
      $table->dropConstrainedForeignId('kegiatan_id');
    });
    Schema::dropIfExists('tabel_web_galeri_kegiatan');
  }
};
