<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Fondasi mapel: mapping menjadi per-kurikulum dan membawa
   * kelompok penempatannya sendiri, sekolah dapat menonaktifkan
   * mapel pokok yang tidak diajarkan, dan unique mapping ditutup
   * di level database.
   *
   * - kurikulum_id NULL berarti berlaku untuk semua kurikulum
   *   (konsisten dengan penandaan kurikulum di master).
   * - mapel_kelompok_id diisi dari kelompok master yang ada
   *   (backfill), setelah ini menjadi milik mapping.
   * - Unique lama berlubang untuk baris pokok karena sekolah_id
   *   NULL tidak pernah dianggap sama oleh MySQL. Diganti unique
   *   atas kolom ter-generate sekolah_key/kurikulum_key
   *   (COALESCE(..., 0)). Unique baru dibuat SEBELUM yang lama
   *   dihapus karena unique lama menopang FK mapel_master_id.
   */
  public function up(): void
  {
    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      $table->foreignId('kurikulum_id')
        ->nullable()
        ->after('semester_id')
        ->constrained('tabel_kurikulum')
        ->nullOnDelete();

      $table->foreignId('mapel_kelompok_id')
        ->nullable()
        ->after('mapel_master_id')
        ->constrained('tabel_mapel_kelompok')
        ->nullOnDelete();

      $table->boolean('is_aktif')->default(true)->after('urutan');

      $table->unsignedBigInteger('sekolah_key')->storedAs('COALESCE(sekolah_id, 0)');
      $table->unsignedBigInteger('kurikulum_key')->storedAs('COALESCE(kurikulum_id, 0)');
    });

    // Backfill kelompok dari master pemiliknya
    DB::statement('
      UPDATE tabel_mapel_mapping AS mm
      JOIN tabel_mapel_master AS m ON m.id = mm.mapel_master_id
      SET mm.mapel_kelompok_id = m.mapel_kelompok_id
      WHERE mm.mapel_kelompok_id IS NULL
    ');

    // Backfill kurikulum hanya bila master bertanda tepat satu
    // kurikulum; bertanda banyak atau tidak sama sekali tetap
    // NULL (= berlaku semua kurikulum)
    DB::statement('
      UPDATE tabel_mapel_mapping AS mm
      JOIN (
        SELECT mapel_master_id, MIN(kurikulum_id) AS kid
        FROM tabel_mapel_kurikulum
        GROUP BY mapel_master_id
        HAVING COUNT(*) = 1
      ) AS t ON t.mapel_master_id = mm.mapel_master_id
      SET mm.kurikulum_id = t.kid
      WHERE mm.kurikulum_id IS NULL
    ');

    // Unique baru dulu (menopang FK mapel_master_id) ...
    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      $table->unique(
        ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_key', 'kurikulum_key'],
        'uniq_mapel_mapping_v2'
      );
    });

    // ... baru unique lama dihapus
    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      $table->dropUnique('uniq_mapel_mapping');
    });

    // Daftar mapel pokok yang dinonaktifkan per sekolah
    Schema::create('tabel_mapel_nonaktif_sekolah', function (Blueprint $table) {
      $table->id();
      $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
      $table->foreignId('mapel_mapping_id')->constrained('tabel_mapel_mapping')->cascadeOnDelete();
      $table->timestamps();

      $table->unique(['sekolah_id', 'mapel_mapping_id'], 'uniq_nonaktif_sekolah_mapping');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_mapel_nonaktif_sekolah');

    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      $table->unique(
        ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_id'],
        'uniq_mapel_mapping'
      );
    });

    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      $table->dropUnique('uniq_mapel_mapping_v2');
      $table->dropColumn(['sekolah_key', 'kurikulum_key', 'is_aktif']);
      $table->dropConstrainedForeignId('kurikulum_id');
      $table->dropConstrainedForeignId('mapel_kelompok_id');
    });
  }
};
