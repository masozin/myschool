<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 (lanjutan) — potongan penerima menjadi
 * nominal RUPIAH per komponen dan kategori grup menjadi
 * tiga nilai.
 *
 * bos_spj_penerima: kolom ppn_rp / pph_rp / sspd_rp /
 * admin_rp (decimal, bawaan 0) menyimpan potongan
 * PPN, PPh, SSPD, dan biaya admin per penerima dalam
 * rupiah — isian bawaannya berasal dari pajak nota di
 * BKU dan dapat diubah manual. pph_rp diisi ulang dari
 * persen lama: ROUND(volume * satuan * pph_persen / 100)
 * agar nilai diterima penerima lama tidak berubah.
 * Kolom pph_persen lama tidak dipakai lagi sesudah ini.
 *
 * bos_spj_grup: kolom kategori (string, nullable)
 * menggantikan override boolean non_barang. Nilai sah:
 * 'barang' | 'barang_konsumsi' | 'non_barang'; null =
 * ikut deteksi otomatis. Isi ulang dari kolom lama:
 * non_barang true -> 'non_barang', false -> 'barang',
 * null tetap null.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (Schema::hasTable('bos_spj_penerima')) {
      $kolomPotongan = ['ppn_rp', 'pph_rp', 'sspd_rp', 'admin_rp'];
      $kolomBaru = array_values(array_filter(
        $kolomPotongan,
        fn($kolom) => !Schema::hasColumn('bos_spj_penerima', $kolom)
      ));
      if ($kolomBaru) {
        Schema::table('bos_spj_penerima', function (Blueprint $table) use ($kolomBaru) {
          foreach ($kolomBaru as $kolom) {
            $table->decimal($kolom, 15, 2)->default(0);
          }
        });
      }

      // Isi ulang PPh rupiah dari persen lama agar
      // diterima penerima yang sudah tersimpan tidak
      // berubah nilainya.
      if (Schema::hasColumn('bos_spj_penerima', 'pph_persen')
        && Schema::hasColumn('bos_spj_penerima', 'pph_rp')) {
        DB::table('bos_spj_penerima')->update([
          'pph_rp' => DB::raw('ROUND(volume * satuan * pph_persen / 100)'),
        ]);
      }
    }

    if (Schema::hasTable('bos_spj_grup') && !Schema::hasColumn('bos_spj_grup', 'kategori')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->string('kategori', 20)->nullable();
      });
    }

    // Isi ulang kategori dari override boolean lama.
    if (Schema::hasTable('bos_spj_grup')
      && Schema::hasColumn('bos_spj_grup', 'kategori')
      && Schema::hasColumn('bos_spj_grup', 'non_barang')) {
      DB::table('bos_spj_grup')
        ->where('non_barang', true)
        ->update(['kategori' => 'non_barang']);
      DB::table('bos_spj_grup')
        ->where('non_barang', false)
        ->update(['kategori' => 'barang']);
    }
  }

  public function down(): void
  {
    if (Schema::hasTable('bos_spj_penerima')) {
      $kolomAda = array_values(array_filter(
        ['ppn_rp', 'pph_rp', 'sspd_rp', 'admin_rp'],
        fn($kolom) => Schema::hasColumn('bos_spj_penerima', $kolom)
      ));
      if ($kolomAda) {
        Schema::table('bos_spj_penerima', function (Blueprint $table) use ($kolomAda) {
          $table->dropColumn($kolomAda);
        });
      }
    }

    if (Schema::hasTable('bos_spj_grup') && Schema::hasColumn('bos_spj_grup', 'kategori')) {
      Schema::table('bos_spj_grup', function (Blueprint $table) {
        $table->dropColumn('kategori');
      });
    }
  }
};
