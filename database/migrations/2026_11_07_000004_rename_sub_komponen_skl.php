<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename mengikuti pola induk-dulu (konsisten dengan
 * tabel_skl_nilai_komponen / skl_nilai_komponen_id):
 *
 *   tabel_skl_sub_komponen        → tabel_skl_komponen_sub
 *   tabel_skl_nilai_sub_komponen  → tabel_skl_nilai_komponen_sub
 *   kolom sub_komponen_id         → komponen_sub_id
 *
 * Data TIDAK hilang — semua operasi adalah rename.
 *
 * Sekalian: keterangan di tabel sub dibuat nullable. Kolom itu
 * semula NOT NULL tanpa default sementara tidak pernah diisi
 * kode mana pun, sehingga tambah sub-komponen selalu gagal
 * MySQL 1364; tabel induknya (tabel_skl_komponen) memang sudah
 * nullable sejak awal.
 */
return new class extends Migration
{
  public function up(): void
  {
    // 1) Lepas FK + unique yang melibatkan kolom lama
    if (Schema::hasTable('tabel_skl_nilai_sub_komponen')) {
      Schema::table('tabel_skl_nilai_sub_komponen', function (Blueprint $table) {
        $table->dropForeign(['sub_komponen_id']);
        $table->dropUnique('unique_nilai_sub');
      });
    }

    // 2) Rename tabel
    if (Schema::hasTable('tabel_skl_sub_komponen')) {
      Schema::rename('tabel_skl_sub_komponen', 'tabel_skl_komponen_sub');
    }
    if (Schema::hasTable('tabel_skl_nilai_sub_komponen')) {
      Schema::rename('tabel_skl_nilai_sub_komponen', 'tabel_skl_nilai_komponen_sub');
    }

    // 3) Rename kolom + pasang kembali FK & unique
    if (Schema::hasColumn('tabel_skl_nilai_komponen_sub', 'sub_komponen_id')) {
      Schema::table('tabel_skl_nilai_komponen_sub', function (Blueprint $table) {
        $table->renameColumn('sub_komponen_id', 'komponen_sub_id');
      });
      Schema::table('tabel_skl_nilai_komponen_sub', function (Blueprint $table) {
        $table->foreign('komponen_sub_id')
          ->references('id')
          ->on('tabel_skl_komponen_sub')
          ->cascadeOnDelete();
        $table->unique(
          ['skl_nilai_komponen_id', 'komponen_sub_id'],
          'unique_nilai_komponen_sub'
        );
      });
    }

    // 4) keterangan boleh kosong
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::table('tabel_skl_komponen_sub', function (Blueprint $table) {
        $table->string('keterangan', 100)->nullable()->change();
      });
    }
  }

  public function down(): void
  {
    // keterangan kembali NOT NULL — gagal bila sudah ada baris
    // berketerangan kosong, memang disengaja agar tidak senyap.
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::table('tabel_skl_komponen_sub', function (Blueprint $table) {
        $table->string('keterangan', 100)->nullable(false)->change();
      });
    }

    if (Schema::hasColumn('tabel_skl_nilai_komponen_sub', 'komponen_sub_id')) {
      Schema::table('tabel_skl_nilai_komponen_sub', function (Blueprint $table) {
        $table->dropForeign(['komponen_sub_id']);
        $table->dropUnique('unique_nilai_komponen_sub');
      });
      Schema::table('tabel_skl_nilai_komponen_sub', function (Blueprint $table) {
        $table->renameColumn('komponen_sub_id', 'sub_komponen_id');
      });
      Schema::table('tabel_skl_nilai_komponen_sub', function (Blueprint $table) {
        $table->foreign('sub_komponen_id')
          ->references('id')
          ->on('tabel_skl_komponen_sub')
          ->cascadeOnDelete();
        $table->unique(
          ['skl_nilai_komponen_id', 'sub_komponen_id'],
          'unique_nilai_sub'
        );
      });
    }

    if (Schema::hasTable('tabel_skl_nilai_komponen_sub')) {
      Schema::rename('tabel_skl_nilai_komponen_sub', 'tabel_skl_nilai_sub_komponen');
    }
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::rename('tabel_skl_komponen_sub', 'tabel_skl_sub_komponen');
    }
  }
};
