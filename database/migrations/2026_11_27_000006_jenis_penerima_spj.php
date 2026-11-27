<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — jenis penerima: orang / toko.
 *
 * bos_spj_penerima: kolom jenis (string, bawaan 'orang')
 * membedakan penerima perorangan (PTK / induk penerima /
 * ketikan manual orang) dari penerima TOKO atau usaha
 * (mis. nama toko pada nota) yang tidak punya data
 * perorangan (NIP, pangkat, NIK). Editor penerima
 * memakai penanda ini untuk menyembunyikan isian
 * perorangan pada baris toko.
 *
 * Isi ulang heuristik: snapshot lama tanpa tautan
 * (ptk_id & penerima_id kosong) yang namanya sama
 * persis dengan nama_toko salah satu nota di sekolah
 * yang sama dianggap penerima toko.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_penerima')
      && !Schema::hasColumn('bos_spj_penerima', 'jenis')) {
      Schema::table('bos_spj_penerima', function (Blueprint $table) {
        $table->string('jenis', 10)->default('orang');
      });
    }

    // Isi ulang heuristik dari nama toko nota: untuk
    // tiap pasangan (sekolah, nama_toko) yang tidak
    // kosong, snapshot tanpa tautan yang namanya sama
    // persis ditandai sebagai toko.
    if (Schema::hasTable('bos_spj_penerima')
      && Schema::hasColumn('bos_spj_penerima', 'jenis')
      && Schema::hasTable('bos_kas_nota')) {
      $pasanganToko = DB::table('bos_kas_nota')
        ->select('sekolah_id', 'nama_toko')
        ->whereNotNull('nama_toko')
        ->where('nama_toko', '<>', '')
        ->distinct()
        ->get();
      foreach ($pasanganToko as $toko) {
        DB::table('bos_spj_penerima')
          ->where('sekolah_id', $toko->sekolah_id)
          ->where('nama', $toko->nama_toko)
          ->whereNull('ptk_id')
          ->whereNull('penerima_id')
          ->update(['jenis' => 'toko']);
      }
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('bos_spj_penerima')
      && Schema::hasColumn('bos_spj_penerima', 'jenis')) {
      Schema::table('bos_spj_penerima', function (Blueprint $table) {
        $table->dropColumn('jenis');
      });
    }
  }
};
