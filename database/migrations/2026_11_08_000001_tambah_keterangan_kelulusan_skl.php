<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom keterangan kelulusan pada tabel_skl.
 *
 * Templat cetak SKL sejak awal membaca $skl->keterangan untuk
 * baris putusan "LULUS / TIDAK LULUS", tetapi kolomnya tidak
 * pernah dibuat — baris itu selalu tercetak kosong. Kolom ini
 * melengkapinya: bawaan 'LULUS' (jalur buat mana pun), dapat
 * diubah per siswa sebelum SKL disetujui. Baris lama yang
 * sudah disetujui diisi 'LULUS' oleh migrasi ini.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_skl', 'keterangan')) {
      Schema::table('tabel_skl', function (Blueprint $table) {
        $table->string('keterangan', 20)->nullable()->default('LULUS')->after('status');
      });
    }

    DB::table('tabel_skl')
      ->where('status', 'disetujui')
      ->whereNull('keterangan')
      ->update(['keterangan' => 'LULUS']);
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_skl', 'keterangan')) {
      Schema::table('tabel_skl', function (Blueprint $table) {
        $table->dropColumn('keterangan');
      });
    }
  }
};
