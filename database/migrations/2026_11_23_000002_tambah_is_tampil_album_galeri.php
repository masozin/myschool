<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pilihan tampil/tidak untuk album galeri: album bisa disembunyikan
 * dari halaman web publik (mis. masih disiapkan / dokumentasi
 * internal) tanpa menghapus foto-fotonya. Album lama otomatis
 * tampil (default true), perilaku publik tidak berubah.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('tabel_web_galeri_kegiatan', function (Blueprint $table) {
      $table->boolean('is_tampil')->default(true)->after('keterangan');
    });
  }

  public function down(): void
  {
    Schema::table('tabel_web_galeri_kegiatan', function (Blueprint $table) {
      $table->dropColumn('is_tampil');
    });
  }
};
