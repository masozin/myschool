<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom npwp di tabel_ptk (ditambahkan oleh migrasi
 * 2026_11_26_000001 untuk kebutuhan SPJ) diganti menjadi nik:
 * yang dipakai untuk daftar penerima adalah Nomor Induk
 * Kependudukan (16 digit), bukan NPWP. Data yang sudah terisi
 * di kolom lama ikut terbawa karena ini rename, bukan drop.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'npwp') && !Schema::hasColumn('tabel_ptk', 'nik')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->renameColumn('npwp', 'nik');
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'nik') && !Schema::hasColumn('tabel_ptk', 'npwp')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->renameColumn('nik', 'npwp');
      });
    }
  }
};
