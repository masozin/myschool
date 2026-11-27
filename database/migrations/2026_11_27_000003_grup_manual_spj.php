<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ — grup manual (gabung / pecah nota).
 *
 * Pengelompokan bawaan SPJ mengikuti nota ARKAS (satu
 * nota = satu grup kwitansi). Dua tabel ini menambah
 * lapisan pengelompokan MANUAL milik pengguna:
 * - bos_spj_grup_manual: satu grup buatan pengguna
 *   dengan nama alias — hasil menggabungkan beberapa
 *   nota / nomor bukti (gabung) atau memecah satu nota
 *   menjadi beberapa bagian (pecah). Alias tampil di
 *   tabel BKU dan menjadi uraian pembayaran kwitansi.
 * - bos_spj_grup_anggota: baris-baris belanja BKU yang
 *   menjadi anggota grup manual. Satu baris hanya boleh
 *   di SATU grup manual (unik bos_kas_umum_id);
 *   keanggotaan ini menimpa pengelompokan bawaan ARKAS
 *   untuk keperluan SPJ.
 *
 * Kunci grupnya "manual:{id}" mengikuti pola kunci grup
 * kwitansi di KelolaBosController ("nota:{id}" /
 * "bukti:{no}|{tgl}" / "baris:{id}"), sehingga penerima
 * (bos_spj_penerima) dan meta (bos_spj_grup) menempel
 * dengan cara yang sama. Ini tabel KERJA milik pengguna
 * di myschool — data cermin ARKAS (bos_kas_umum /
 * bos_kas_nota) TIDAK pernah diubah oleh fitur ini.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_spj_grup_manual')) {
      Schema::create('bos_spj_grup_manual', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('nama', 120);
        $table->timestamps();
        $table->index('sekolah_id');
      });
    }

    if (!Schema::hasTable('bos_spj_grup_anggota')) {
      Schema::create('bos_spj_grup_anggota', function (Blueprint $table) {
        $table->id();
        $table->foreignId('grup_manual_id')->constrained('bos_spj_grup_manual')->cascadeOnDelete();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('bos_kas_umum_id')->constrained('bos_kas_umum')->cascadeOnDelete();
        $table->timestamps();
        $table->unique('bos_kas_umum_id');
        $table->index(['sekolah_id', 'grup_manual_id']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_spj_grup_anggota');
    Schema::dropIfExists('bos_spj_grup_manual');
  }
};
