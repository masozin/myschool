<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kelola BOS Fase 1.5 — referensi kode + versi kertas kerja.
 *
 * - bos_ref_acuan_barang: cermin ref_acuan_barang ARKAS (kode
 *   barang + harga acuan SSH). Kunci ARKAS komposit
 *   (id_barang, tahun) → id_arkas = "<id_barang>#<tahun>".
 * - bos_ref_satuan: cermin ref_satuan ARKAS.
 * - bos_anggaran: + is_revisi & arkas_create_date supaya
 *   versi kertas kerja per sumber dana bisa diurutkan dan
 *   dilabeli (RKAS Awal / Revisi 1 / 2 / ...).
 * - bos_ref_kode: ARKAS menyimpan ref_kode dengan kunci
 *   komposit (id_ref_kode, tahun, sumber_dana_id,
 *   bentuk_pendidikan_id) — satu id_ref_kode muncul berkali-
 *   kali. Fase 1 hanya menyimpan yang terpakai dengan
 *   id_arkas = id_ref_kode; mulai fase ini id_arkas menjadi
 *   kunci komposit dan id_ref_kode mentah disimpan di kolom
 *   sendiri untuk keperluan join. Kolom id_arkas diperlebar
 *   ke 50 (kunci komposit ref_kode ±35 karakter, acuan
 *   barang ±43).
 *
 * Semua langkah dijaga hasTable/hasColumn agar aman diulang
 * dari keadaan setengah jalan (pola migrasi Kelola BOS).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_ref_acuan_barang')) {
      Schema::create('bos_ref_acuan_barang', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        // id_arkas = "<id_barang>#<tahun>" (kunci ARKAS komposit)
        $table->string('id_arkas', 50);
        // id_barang/nama_barang/blok_id boleh null: ARKAS
        // memuat baris sampah dengan string kosong / spasi
        // saja (2 baris id+nama kosong, 2 baris nama hanya
        // spasi) yang tiba sebagai null lewat middleware
        // Laravel — pola yang sama seperti kode_bku Fase 1.
        $table->string('id_barang', 40)->nullable();
        $table->string('kode_rekening', 20)->nullable();
        $table->string('nama_barang', 255)->nullable();
        $table->string('satuan', 30)->nullable();
        $table->string('blok_id', 6)->nullable();
        $table->string('kode_belanja', 4)->nullable();
        $table->decimal('harga_barang', 15, 2)->nullable();
        $table->decimal('batas_bawah', 15, 2)->nullable();
        $table->decimal('batas_atas', 15, 2)->nullable();
        $table->unsignedSmallInteger('tahun');
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'nama_barang']);
        $table->index(['sekolah_id', 'kode_rekening']);
        $table->index(['sekolah_id', 'tahun']);
      });
    }

    if (!Schema::hasTable('bos_ref_satuan')) {
      Schema::create('bos_ref_satuan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('satuan', 30);
        $table->string('unit', 30);
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
      });
    }

    if (Schema::hasTable('bos_anggaran')) {
      Schema::table('bos_anggaran', function (Blueprint $table) {
        if (!Schema::hasColumn('bos_anggaran', 'is_revisi')) {
          $table->unsignedSmallInteger('is_revisi')->default(0);
        }
        if (!Schema::hasColumn('bos_anggaran', 'arkas_create_date')) {
          $table->dateTime('arkas_create_date')->nullable();
        }
      });
    }

    if (Schema::hasTable('bos_ref_kode')) {
      Schema::table('bos_ref_kode', function (Blueprint $table) {
        if (!Schema::hasColumn('bos_ref_kode', 'id_ref_kode')) {
          $table->string('id_ref_kode', 30)->nullable();
        }
        if (!Schema::hasColumn('bos_ref_kode', 'sumber_dana_id')) {
          $table->unsignedInteger('sumber_dana_id')->nullable();
        }
        if (!Schema::hasColumn('bos_ref_kode', 'bentuk_pendidikan_id')) {
          $table->unsignedSmallInteger('bentuk_pendidikan_id')->nullable();
        }
      });
      Schema::table('bos_ref_kode', function (Blueprint $table) {
        $table->index(['sekolah_id', 'id_ref_kode', 'tahun'], 'bos_ref_kode_join_idx');
      });
      // Perlebar id_arkas untuk kunci komposit (MySQL saja;
      // SQLite tidak menegakkan panjang varchar).
      if (DB::getDriverName() === 'mysql') {
        DB::statement('ALTER TABLE `bos_ref_kode` MODIFY `id_arkas` VARCHAR(50) NOT NULL');
      }
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_ref_acuan_barang');
    Schema::dropIfExists('bos_ref_satuan');

    if (Schema::hasTable('bos_anggaran')) {
      Schema::table('bos_anggaran', function (Blueprint $table) {
        $kolom = [];
        if (Schema::hasColumn('bos_anggaran', 'is_revisi')) {
          $kolom[] = 'is_revisi';
        }
        if (Schema::hasColumn('bos_anggaran', 'arkas_create_date')) {
          $kolom[] = 'arkas_create_date';
        }
        if ($kolom) {
          $table->dropColumn($kolom);
        }
      });
    }

    if (Schema::hasTable('bos_ref_kode')) {
      Schema::table('bos_ref_kode', function (Blueprint $table) {
        $table->dropIndex('bos_ref_kode_join_idx');
        $kolom = [];
        foreach (['id_ref_kode', 'sumber_dana_id', 'bentuk_pendidikan_id'] as $k) {
          if (Schema::hasColumn('bos_ref_kode', $k)) {
            $kolom[] = $k;
          }
        }
        if ($kolom) {
          $table->dropColumn($kolom);
        }
      });
      if (DB::getDriverName() === 'mysql') {
        DB::statement('ALTER TABLE `bos_ref_kode` MODIFY `id_arkas` VARCHAR(30) NOT NULL');
      }
    }
  }
};
