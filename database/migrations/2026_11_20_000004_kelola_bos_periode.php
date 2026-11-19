<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelola BOS Fase 1.6 — jadwal bulanan & realisasi BKU.
 *
 * - bos_rapbs_periode: cermin rapbs_periode ARKAS — jadwal
 *   per baris kertas kerja menurut periode. id_periode
 *   81–92 = Januari–Desember, 1–4 = Triwulan 1–4. Halaman
 *   Kertas Kerja memakai baris bulanan untuk kolom Jadwal
 *   (yang bisa dianggarkan sampai bulan tertentu).
 * - bos_ref_periode: cermin ref_periode ARKAS (nama periode).
 *
 * kas_umum ARKAS terhubung ke jadwal lewat
 * kas_umum.id_rapbs_periode — kolom itu sudah ada di cermin
 * bos_kas_umum sejak Fase 1, jadi realisasi per baris bisa
 * dihitung tanpa mengubah tabel transaksi.
 *
 * Semua langkah dijaga hasTable agar aman diulang dari
 * keadaan setengah jalan (pola migrasi Kelola BOS).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_rapbs_periode')) {
      Schema::create('bos_rapbs_periode', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('id_rapbs_arkas', 30);
        $table->unsignedSmallInteger('id_periode');
        $table->decimal('volume', 12, 2);
        // satuan di ARKAS NOT NULL, tetapi boleh null di
        // cermin: string kosong dari ARKAS tiba sebagai
        // null lewat middleware (preseden Fase 1/1.5).
        $table->string('satuan', 30)->nullable();
        $table->decimal('harga_satuan', 15, 2);
        $table->decimal('jumlah', 15, 2);
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'id_rapbs_arkas']);
        $table->index(['sekolah_id', 'id_periode']);
      });
    }

    if (!Schema::hasTable('bos_ref_periode')) {
      Schema::create('bos_ref_periode', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        // id_arkas = string dari id_periode ARKAS ("81", "1", ...)
        $table->string('id_arkas', 10);
        $table->unsignedSmallInteger('id_periode');
        $table->string('periode', 50);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'id_periode']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_rapbs_periode');
    Schema::dropIfExists('bos_ref_periode');
  }
};
