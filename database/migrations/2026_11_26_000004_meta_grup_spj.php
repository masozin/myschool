<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — meta per grup transaksi BKU.
 *
 * Satu baris per grup kwitansi (grup_kunci yang sama
 * dengan bos_spj_penerima) menyimpan keterangan kerja
 * SPJ yang TIDAK tersedia di data ARKAS:
 * - status: 'belum' (bawaan) / 'lunas' / 'selesai' —
 *   saklar keterangan penyelesaian grup di halaman BKU.
 * - nomor_va: nomor virtual account pembayaran SIPLah,
 *   diisi MANUAL karena data ARKAS/SIPLah yang tersinkron
 *   tidak memuat nomor VA (details nota hanya berisi
 *   nomor invoice, marketplace, merchant, tanggal bayar).
 *
 * Ini tabel KERJA milik pengguna di myschool — bukan
 * cermin ARKAS, tidak disentuh agen sinkron.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_spj_grup')) {
      Schema::create('bos_spj_grup', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('grup_kunci', 120);
        $table->string('status', 20)->default('belum');
        $table->string('nomor_va', 50)->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'grup_kunci']);
        $table->index('grup_kunci');
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_spj_grup');
  }
};
