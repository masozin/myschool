<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelola BOS Fase 1.7 — nama alias (lapisan kerja pertama).
 *
 * - bos_nama_alias: alias nama tampilan yang dibuat pengguna
 *   sendiri untuk nama-nama panjang dari ARKAS (uraian rapbs
 *   & kas_umum, nama barang acuan). Ini tabel KERJA milik
 *   pengguna di myschool — BUKAN cermin ARKAS: tidak ada
 *   id_arkas dan tidak ada yang dikirim agen sinkron.
 *   nama_sumber menyimpan nama asli persis seperti dari
 *   ARKAS; alias kosong berarti baris dihapus (kembali ke
 *   nama asli), jadi tabel hanya memuat alias yang aktif.
 *
 * Semua langkah dijaga hasTable agar aman diulang dari
 * keadaan setengah jalan (pola migrasi Kelola BOS).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_nama_alias')) {
      Schema::create('bos_nama_alias', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('nama_sumber', 500);
        $table->string('alias', 255);
        $table->timestamps();
        $table->unique(['sekolah_id', 'nama_sumber']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_nama_alias');
  }
};
