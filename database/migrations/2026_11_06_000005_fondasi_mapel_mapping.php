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
   *   atas kolom kunci sekolah_key/kurikulum_key (cermin dengan
   *   NULL dihitung 0). Kolom kunci adalah kolom BIASA yang
   *   kesegarannya dijaga model MapelMapping — kolom ter-generate
   *   tidak dipakai karena MySQL menolaknya di atas kolom yang
   *   memiliki foreign key (error 1215). Unique baru dibuat
   *   SEBELUM yang lama dihapus karena unique lama menopang FK
   *   mapel_master_id.
   * - Semua langkah dijaga hasColumn/hasIndex/cek information
   *   schema: migrasi versi pertama berhenti di tengah pada
   *   sebagian database (beberapa kolom sempat terbuat), dan
   *   versi ini harus aman dijalankan dari keadaan parsial itu.
   */
  public function up(): void
  {
    $this->tambahKolomFk('kurikulum_id', 'tabel_kurikulum', 'semester_id');
    $this->tambahKolomFk('mapel_kelompok_id', 'tabel_mapel_kelompok', 'mapel_master_id');

    if (!Schema::hasColumn('tabel_mapel_mapping', 'is_aktif')) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
        $table->boolean('is_aktif')->default(true)->after('urutan');
      });
    }

    $this->pastikanKolomKunci('sekolah_key');
    $this->pastikanKolomKunci('kurikulum_key');

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

    // Backfill kolom kunci
    DB::statement('
      UPDATE tabel_mapel_mapping
      SET sekolah_key = COALESCE(sekolah_id, 0),
          kurikulum_key = COALESCE(kurikulum_id, 0)
    ');

    // Unique baru dulu (menopang FK mapel_master_id) ...
    if (!Schema::hasIndex('tabel_mapel_mapping', 'uniq_mapel_mapping_v2')) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
        $table->unique(
          ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_key', 'kurikulum_key'],
          'uniq_mapel_mapping_v2'
        );
      });
    }

    // ... baru unique lama dihapus
    if (Schema::hasIndex('tabel_mapel_mapping', 'uniq_mapel_mapping')) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
        $table->dropUnique('uniq_mapel_mapping');
      });
    }

    // Daftar mapel pokok yang dinonaktifkan per sekolah
    if (!Schema::hasTable('tabel_mapel_nonaktif_sekolah')) {
      Schema::create('tabel_mapel_nonaktif_sekolah', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('mapel_mapping_id')->constrained('tabel_mapel_mapping')->cascadeOnDelete();
        $table->timestamps();

        $table->unique(['sekolah_id', 'mapel_mapping_id'], 'uniq_nonaktif_sekolah_mapping');
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_mapel_nonaktif_sekolah');

    if (!Schema::hasIndex('tabel_mapel_mapping', 'uniq_mapel_mapping')) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
        $table->unique(
          ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_id'],
          'uniq_mapel_mapping'
        );
      });
    }

    if (Schema::hasIndex('tabel_mapel_mapping', 'uniq_mapel_mapping_v2')) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
        $table->dropUnique('uniq_mapel_mapping_v2');
      });
    }

    Schema::table('tabel_mapel_mapping', function (Blueprint $table) {
      if (Schema::hasColumn('tabel_mapel_mapping', 'sekolah_key')) {
        $table->dropColumn('sekolah_key');
      }
      if (Schema::hasColumn('tabel_mapel_mapping', 'kurikulum_key')) {
        $table->dropColumn('kurikulum_key');
      }
      if (Schema::hasColumn('tabel_mapel_mapping', 'is_aktif')) {
        $table->dropColumn('is_aktif');
      }
      if (Schema::hasColumn('tabel_mapel_mapping', 'kurikulum_id')) {
        $table->dropConstrainedForeignId('kurikulum_id');
      }
      if (Schema::hasColumn('tabel_mapel_mapping', 'mapel_kelompok_id')) {
        $table->dropConstrainedForeignId('mapel_kelompok_id');
      }
    });
  }

  /**
   * Tambahkan kolom FK bila belum ada — kolom dan constraint-nya
   * dijaga terpisah, karena percobaan migrasi yang berhenti di
   * tengah bisa meninggalkan kolom tanpa constraint.
   */
  private function tambahKolomFk(string $kolom, string $tabelRef, string $after): void
  {
    if (!Schema::hasColumn('tabel_mapel_mapping', $kolom)) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) use ($kolom, $after) {
        $table->unsignedBigInteger($kolom)->nullable()->after($after);
      });
    }

    $namaFk = 'tabel_mapel_mapping_' . $kolom . '_foreign';
    $ada = DB::selectOne(
      "SELECT COUNT(*) AS c FROM information_schema.TABLE_CONSTRAINTS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'tabel_mapel_mapping'
         AND CONSTRAINT_NAME = ?
         AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
      [$namaFk]
    );

    if ((int) ($ada->c ?? 0) === 0) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) use ($kolom, $tabelRef) {
        $table->foreign($kolom)->references('id')->on($tabelRef)->nullOnDelete();
      });
    }
  }

  /**
   * Pastikan kolom kunci adalah kolom biasa. Bila percobaan
   * migrasi sebelumnya sempat membuatnya sebagai kolom
   * ter-generate (yang tidak bisa di-UPDATE untuk backfill),
   * buang dan buat ulang.
   */
  private function pastikanKolomKunci(string $kolom): void
  {
    $baris = DB::selectOne(
      "SELECT EXTRA FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'tabel_mapel_mapping'
         AND COLUMN_NAME = ?",
      [$kolom]
    );

    if ($baris && stripos((string) ($baris->EXTRA ?? ''), 'GENERATED') !== false) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) use ($kolom) {
        $table->dropColumn($kolom);
      });
    }

    if (!Schema::hasColumn('tabel_mapel_mapping', $kolom)) {
      Schema::table('tabel_mapel_mapping', function (Blueprint $table) use ($kolom) {
        $table->unsignedBigInteger($kolom)->default(0);
      });
    }
  }
};
