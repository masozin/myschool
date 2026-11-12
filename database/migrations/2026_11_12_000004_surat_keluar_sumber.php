<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom keterhubungan Surat Keluar dengan modul asalnya.
 *
 * sumber/sumber_id menunjuk dokumen asal surat yang lahir di
 * modul lain (Fase A: 'surat-pindah' → tabel_siswa_registrasi;
 * berikutnya SKHU/SKL per angkatan). data_cetak membekukan isi
 * surat yang dihasilkan modul asal (snapshot) supaya cetak
 * ulang selalu identik dengan saat terbit. Surat yang dibuat
 * manual dari agenda membiarkan ketiganya kosong.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('tabel_surat_keluar')) {
      return;
    }

    Schema::table('tabel_surat_keluar', function (Blueprint $table) {
      if (!Schema::hasColumn('tabel_surat_keluar', 'sumber')) {
        $table->string('sumber', 30)->nullable();
      }
      if (!Schema::hasColumn('tabel_surat_keluar', 'sumber_id')) {
        $table->unsignedBigInteger('sumber_id')->nullable();
      }
      if (!Schema::hasColumn('tabel_surat_keluar', 'data_cetak')) {
        $table->json('data_cetak')->nullable();
      }
    });

    if (!Schema::hasIndex('tabel_surat_keluar', 'surat_keluar_sumber_index')) {
      Schema::table('tabel_surat_keluar', function (Blueprint $table) {
        $table->index(['sumber', 'sumber_id'], 'surat_keluar_sumber_index');
      });
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('tabel_surat_keluar')) {
      return;
    }

    Schema::table('tabel_surat_keluar', function (Blueprint $table) {
      if (Schema::hasIndex('tabel_surat_keluar', 'surat_keluar_sumber_index')) {
        $table->dropIndex('surat_keluar_sumber_index');
      }
      foreach (['sumber', 'sumber_id', 'data_cetak'] as $kolom) {
        if (Schema::hasColumn('tabel_surat_keluar', $kolom)) {
          $table->dropColumn($kolom);
        }
      }
    });
  }
};
