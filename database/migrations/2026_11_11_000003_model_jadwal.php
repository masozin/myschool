<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Model jadwal lebih dari satu per sekolah+semester.
   *
   * Selama ini kerangka jam hanya satu untuk semua rombel,
   * padahal kelas bawah dan kelas atas lazimnya beda pola jam
   * (durasi per jam, jumlah jam, jam mulai). Kini satu
   * sekolah+semester boleh punya banyak baris pengaturan —
   * disebut MODEL — dan tiap model dapat dibatasi pada rentang
   * tingkat (mis. "Kelas Bawah" untuk tingkat 1–3, "Kelas Atas"
   * untuk 4–6; kosong = semua tingkat/model umum).
   *
   * Perubahan:
   * - tambah kolom nama, tingkat_dari, tingkat_sampai;
   * - unique(sekolah_id, semester_id) 'uniq_jadwal_pengaturan'
   *   diturunkan menjadi index biasa agar banyak model bisa
   *   hidup berdampingan;
   * - baris lama diberi nama 'Utama' (rentang kosong = umum,
   *   sehingga perilaku lama tidak berubah).
   *
   * Dijaga hasColumn/getIndexes agar aman diulang.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_jadwal_pengaturan')) {
      return;
    }

    if (!Schema::hasColumn('tabel_jadwal_pengaturan', 'nama')) {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->string('nama', 50)->nullable()->after('semester_id');
      });
    }

    if (!Schema::hasColumn('tabel_jadwal_pengaturan', 'tingkat_dari')) {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->unsignedTinyInteger('tingkat_dari')->nullable()->after('nama');
      });
    }

    if (!Schema::hasColumn('tabel_jadwal_pengaturan', 'tingkat_sampai')) {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->unsignedTinyInteger('tingkat_sampai')->nullable()->after('tingkat_dari');
      });
    }

    $namaIndeks = collect(Schema::getIndexes('tabel_jadwal_pengaturan'))
      ->pluck('name')
      ->all();

    // URUTAN PENTING: index pengganti dibuat DULU, baru unique
    // dihapus. Unique lama menopang foreign key sekolah_id,
    // sehingga MySQL menolak menghapusnya (error 1553) selama
    // belum ada index lain yang kolom pertamanya sekolah_id.
    if (!in_array('idx_jadwal_pengaturan_sekolah_semester', $namaIndeks, true)) {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->index(['sekolah_id', 'semester_id'], 'idx_jadwal_pengaturan_sekolah_semester');
      });
    }

    if (in_array('uniq_jadwal_pengaturan', $namaIndeks, true)) {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->dropUnique('uniq_jadwal_pengaturan');
      });
    }

    DB::table('tabel_jadwal_pengaturan')
      ->whereNull('nama')
      ->update(['nama' => 'Utama']);
  }

  public function down(): void
  {
    if (!Schema::hasTable('tabel_jadwal_pengaturan')) {
      return;
    }

    // Unique dikembalikan hanya masuk akal bila tinggal satu
    // baris per sekolah+semester; bila gagal, kolom tetap aman.
    try {
      Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->unique(['sekolah_id', 'semester_id'], 'uniq_jadwal_pengaturan');
      });
    } catch (\Throwable $e) {
      // abaikan: banyak model masih ada
    }

    foreach (['tingkat_sampai', 'tingkat_dari', 'nama'] as $kolom) {
      if (Schema::hasColumn('tabel_jadwal_pengaturan', $kolom)) {
        Schema::table('tabel_jadwal_pengaturan', function (Blueprint $table) use ($kolom) {
          $table->dropColumn($kolom);
        });
      }
    }
  }
};
