<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — induk penerima bisa berupa
 * TOKO, bukan hanya orang.
 *
 * tabel_penerima selama ini hanya menampung penerima
 * perorangan di luar PTK. Atas permintaan user, toko
 * diperlakukan sama seperti penerima non-PTK: sama-
 * sama data induk yang dikelola sendiri (tambah /
 * ubah / hapus dari modal Daftar Penerima), bedanya
 * jenisnya toko dan rinciannya khas toko.
 *
 * Kolom baru:
 * - jenis   : 'orang' (bawaan) atau 'toko'.
 * - pemilik : nama pemilik toko (khusus toko).
 * - npwp    : NPWP toko (khusus toko; NPWP orang
 *             tidak disimpan — orang memakai NIK).
 * - no_hp   : nomor HP / kontak (dipakai toko).
 *
 * Kolom alamat, no_rekening, dan nama_bank yang sudah
 * ada dipakai bersama oleh kedua jenis.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('tabel_penerima')) {
      return;
    }

    Schema::table('tabel_penerima', function (Blueprint $table) {
      if (!Schema::hasColumn('tabel_penerima', 'jenis')) {
        $table->string('jenis', 10)->default('orang');
      }
      if (!Schema::hasColumn('tabel_penerima', 'pemilik')) {
        $table->string('pemilik')->nullable();
      }
      if (!Schema::hasColumn('tabel_penerima', 'npwp')) {
        $table->string('npwp', 30)->nullable();
      }
      if (!Schema::hasColumn('tabel_penerima', 'no_hp')) {
        $table->string('no_hp', 30)->nullable();
      }
    });
  }

  public function down(): void
  {
    if (!Schema::hasTable('tabel_penerima')) {
      return;
    }

    Schema::table('tabel_penerima', function (Blueprint $table) {
      foreach (['jenis', 'pemilik', 'npwp', 'no_hp'] as $kolom) {
        if (Schema::hasColumn('tabel_penerima', $kolom)) {
          $table->dropColumn($kolom);
        }
      }
    });
  }
};
