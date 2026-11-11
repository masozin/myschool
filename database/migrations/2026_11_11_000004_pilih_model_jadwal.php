<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Model jadwal dipilih per rombel, bukan menurut tingkat.
   *
   * Keputusan Mark: model TIDAK diberi rentang tingkat. Saat
   * menyusun jadwal, Operator memilih model mana yang dipakai
   * rombel itu (pemilih di kepala editor). Karena itu:
   *
   * - tabel_jadwal_pengaturan: kolom tingkat_dari &
   *   tingkat_sampai dari migrasi sebelumnya dibuang;
   * - tabel_rombel: tambah jadwal_pengaturan_id (nullable,
   *   nullOnDelete) — pilihan model rombel pada semesternya
   *   (baris rombel memang per semester). Kosong berarti rombel
   *   mengikuti model pertama / pola bawaan.
   *
   * Dijaga hasColumn agar aman diulang dari keadaan mana pun
   * (termasuk bila migrasi 000003 belum sempat berjalan).
   */
  public function up(): void
  {
    if (Schema::hasTable('tabel_jadwal_pengaturan')) {
      foreach (['tingkat_dari', 'tingkat_sampai'] as $kolom) {
        if (Schema::hasColumn('tabel_jadwal_pengaturan', $kolom)) {
          Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) use ($kolom) {
            $table->dropColumn($kolom);
          });
        }
      }
    }

    if (
      Schema::hasTable('tabel_rombel')
      && !Schema::hasColumn('tabel_rombel', 'jadwal_pengaturan_id')
    ) {
      Schema::table('tabel_rombel', function (Blueprint $table) {
        $table->foreignId('jadwal_pengaturan_id')
          ->nullable()
          ->after('kurikulum_id')
          ->constrained('tabel_jadwal_pengaturan')
          ->nullOnDelete();
      });
    }
  }

  public function down(): void
  {
    if (
      Schema::hasTable('tabel_rombel')
      && Schema::hasColumn('tabel_rombel', 'jadwal_pengaturan_id')
    ) {
      Schema::table('tabel_rombel', function (Blueprint $table) {
        $table->dropConstrainedForeignId('jadwal_pengaturan_id');
      });
    }

    // Kolom tingkat tidak dikembalikan: datanya memang dibuang
    // atas keputusan desain (model dipilih per rombel).
  }
};
