<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Selaraskan tabel kalender_pendidikan dengan kode yang berjalan.
 *
 * Migrasi awal membuat tabel berbasis semester_id, tetapi modul
 * kemudian dilepas dari siklus Semester: model & controller
 * memakai kolom tahun_pelajaran ("2025/2026") dan jenis_semester
 * ("Ganjil"/"Genap", diturunkan dari tanggal). Di database yang
 * sudah berjalan, kedua kolom itu rupanya ditambahkan di luar
 * migrasi — migrasi ini menutup selisihnya secara terjaga:
 * setiap langkah hanya berjalan bila keadaan tabel memang
 * membutuhkannya, jadi aman diulang dari keadaan mana pun.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('kalender_pendidikan', 'tahun_pelajaran')) {
      Schema::table('kalender_pendidikan', function (Blueprint $table) {
        $table->string('tahun_pelajaran', 9)->nullable()->after('id');
      });
    }

    if (!Schema::hasColumn('kalender_pendidikan', 'jenis_semester')) {
      Schema::table('kalender_pendidikan', function (Blueprint $table) {
        $table->string('jenis_semester', 10)->nullable()->after('tahun_pelajaran');
      });
    }

    if (Schema::hasColumn('kalender_pendidikan', 'semester_id')) {
      // Isi tahun/semester dari relasi semester untuk baris lama
      DB::statement(
        'UPDATE kalender_pendidikan kp
         JOIN tabel_semester s ON s.id = kp.semester_id
         SET kp.tahun_pelajaran = s.tahun_pelajaran,
             kp.jenis_semester = s.semester
         WHERE kp.tahun_pelajaran IS NULL'
      );

      // Lepas FK semester_id (nama constraint dibaca dari skema,
      // bukan ditebak), baru kolomnya
      foreach (Schema::getForeignKeys('kalender_pendidikan') as $fk) {
        if (in_array('semester_id', $fk['columns'] ?? [], true)) {
          Schema::table('kalender_pendidikan', function (Blueprint $table) use ($fk) {
            $table->dropForeign($fk['name']);
          });
        }
      }

      Schema::table('kalender_pendidikan', function (Blueprint $table) {
        $table->dropColumn('semester_id');
      });
    }

    // jenis_semester yang masih kosong diturunkan dari tanggal
    DB::statement(
      "UPDATE kalender_pendidikan
       SET jenis_semester = CASE WHEN MONTH(tanggal_mulai) >= 7 THEN 'Ganjil' ELSE 'Genap' END
       WHERE jenis_semester IS NULL"
    );
  }

  public function down(): void
  {
    // Kembalikan kolom semester_id (kosong — pemetaan balik dari
    // tahun_pelajaran ke baris semester tidak disimpan)
    if (!Schema::hasColumn('kalender_pendidikan', 'semester_id')) {
      Schema::table('kalender_pendidikan', function (Blueprint $table) {
        $table->foreignId('semester_id')->nullable()->constrained('tabel_semester')->nullOnDelete();
      });
    }
  }
};
