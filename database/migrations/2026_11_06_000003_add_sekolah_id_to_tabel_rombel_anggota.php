<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Sekolah keanggotaan dibuat eksplisit per baris (nilainya selalu
   * sekolah pemilik rombelnya), dan kekekalan satu baris per siswa
   * per semester dipersempit menjadi per SEKOLAH:
   * unique(siswa_id, semester_id, sekolah_id).
   *
   * Semester bersifat global untuk semua sekolah, jadi unique
   * sebelumnya — (siswa_id, semester_id) — menghalangi mutasi
   * antar sekolah di tengah semester yang sama: siswa yang pindah
   * tidak bisa dimasukkan ke rombel sekolah barunya karena baris
   * sekolah lamanya memakai semester yang sama.
   */
  public function up(): void
  {
    // Dijaga Schema::hasColumn/hasIndex supaya aman dijalankan
    // ulang setelah percobaan pertama yang berhenti di tengah:
    // DDL MySQL tidak bisa di-rollback, jadi sebagian langkah
    // mungkin sudah terlanjur berlaku di database.
    if (!Schema::hasColumn('tabel_rombel_anggota', 'sekolah_id')) {
      Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
        $table->foreignId('sekolah_id')->nullable()->after('semester_id')
          ->constrained('tabel_sekolah');
      });
    }

    // Isi dari rombel masing-masing baris (aman diulang)
    DB::statement(
      'UPDATE tabel_rombel_anggota a
       JOIN tabel_rombel r ON r.id = a.rombel_id
       SET a.sekolah_id = r.sekolah_id'
    );

    // Indeks unik BARU dibuat SEBELUM indeks lama dibuang: indeks
    // lama sedang dipakai MySQL menopang foreign key siswa_id,
    // jadi membuangnya lebih dulu ditolak (error 1553). Indeks
    // baru sama-sama diawali siswa_id, sehingga bisa mengambil
    // alih peran itu.
    if (!Schema::hasIndex('tabel_rombel_anggota', 'tabel_rombel_anggota_siswa_id_semester_id_sekolah_id_unique')) {
      Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
        $table->unique(['siswa_id', 'semester_id', 'sekolah_id']);
      });
    }

    if (Schema::hasIndex('tabel_rombel_anggota', 'tabel_rombel_anggota_siswa_id_semester_id_unique')) {
      Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
        $table->dropUnique(['siswa_id', 'semester_id']);
      });
    }

    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->unsignedBigInteger('sekolah_id')->nullable(false)->change();
    });
  }

  public function down(): void
  {
    // Urutan yang sama seperti up(): indeks pengganti (yang lama)
    // dibuat dulu, baru indeks tiga kolom dibuang — indeks yang
    // sedang menopang foreign key tidak bisa dibuang sendirian.
    if (!Schema::hasIndex('tabel_rombel_anggota', 'tabel_rombel_anggota_siswa_id_semester_id_unique')) {
      Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
        $table->unique(['siswa_id', 'semester_id']);
      });
    }

    if (Schema::hasIndex('tabel_rombel_anggota', 'tabel_rombel_anggota_siswa_id_semester_id_sekolah_id_unique')) {
      Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
        $table->dropUnique(['siswa_id', 'semester_id', 'sekolah_id']);
      });
    }

    Schema::table('tabel_rombel_anggota', function (Blueprint $table) {
      $table->dropForeign(['sekolah_id']);
      $table->dropColumn('sekolah_id');
    });
  }
};
