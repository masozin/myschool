<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Kegiatan pada jadwal (lanjutan fase 1):
   *
   * Sebuah kotak tabel_jadwal kini bisa berisi KEGIATAN selain
   * pelajaran — mis. Pramuka atau Kokurikuler yang menempati jam
   * reguler tetapi bukan mapel. Untuk itu:
   * - pengampu_id menjadi nullable (kotak kegiatan tidak punya
   *   pengampu);
   * - nama_kegiatan: nama bebas kegiatan pengisi kotak;
   * - pembina_id: PTK pembina kegiatan (nullable; baris jadwal
   *   tidak ikut terhapus bila data PTK-nya dihapus).
   *
   * Kegiatan tetap pada garis waktu hari (Upacara, Senam,
   * Literasi, ...) TIDAK memakai migrasi — ia tersimpan di JSON
   * kerangka tabel_jadwal_pengaturan.
   *
   * Dijaga hasColumn/hasTable agar aman diulang.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_jadwal')) {
      return;
    }

    Schema::table('tabel_jadwal', function (Blueprint $table) {
      $table->unsignedBigInteger('pengampu_id')->nullable()->change();
    });

    if (!Schema::hasColumn('tabel_jadwal', 'nama_kegiatan')) {
      Schema::table('tabel_jadwal', function (Blueprint $table) {
        $table->string('nama_kegiatan', 100)->nullable()->after('pengampu_id');
      });
    }

    if (!Schema::hasColumn('tabel_jadwal', 'pembina_id')) {
      Schema::table('tabel_jadwal', function (Blueprint $table) {
        $table->foreignId('pembina_id')
          ->nullable()
          ->after('nama_kegiatan')
          ->constrained('tabel_ptk')
          ->nullOnDelete();
      });
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('tabel_jadwal')) {
      return;
    }

    if (Schema::hasColumn('tabel_jadwal', 'pembina_id')) {
      Schema::table('tabel_jadwal', function (Blueprint $table) {
        $table->dropConstrainedForeignId('pembina_id');
      });
    }

    if (Schema::hasColumn('tabel_jadwal', 'nama_kegiatan')) {
      Schema::table('tabel_jadwal', function (Blueprint $table) {
        $table->dropColumn('nama_kegiatan');
      });
    }

    Schema::table('tabel_jadwal', function (Blueprint $table) {
      $table->unsignedBigInteger('pengampu_id')->nullable(false)->change();
    });
  }
};
