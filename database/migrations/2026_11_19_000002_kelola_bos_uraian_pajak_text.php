<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kelola BOS — perbaikan kolom uraian_pajak di bos_kas_umum.
 *
 * Di ARKAS kolom ini varchar(255), tetapi SQLite ARKAS tidak
 * menegakkan batas panjang: data asli memuat 18 baris uraian_pajak
 * sampai 261 karakter (penjelasan PPN PMK 131/2024, baris ARKAS
 * ke-1481 dst., batch ke-3 agen). MySQL strict menolaknya
 * (Data too long) sehingga sinkron kas-umum gagal dengan
 * HTTP 500 — di SQLite lokal lolos karena batas panjang tidak
 * ditegakkan. Kolom diubah menjadi TEXT agar cermin menampung
 * data apa adanya.
 *
 * Sengaja TIDAK memakai ->change(): itu butuh doctrine/dbal
 * yang tidak terpasang di proyek ini. MySQL diubah lewat
 * ALTER TABLE mentah; SQLite dilewati (di sana panjang
 * varchar memang tidak ditegakkan, jadi tidak ada yang
 * perlu diperbaiki).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_kas_umum') || !Schema::hasColumn('bos_kas_umum', 'uraian_pajak')) {
      return;
    }

    if (DB::getDriverName() === 'mysql') {
      DB::statement('ALTER TABLE `bos_kas_umum` MODIFY `uraian_pajak` TEXT NULL');
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('bos_kas_umum') || !Schema::hasColumn('bos_kas_umum', 'uraian_pajak')) {
      return;
    }

    if (DB::getDriverName() === 'mysql') {
      DB::statement('ALTER TABLE `bos_kas_umum` MODIFY `uraian_pajak` VARCHAR(255) NULL');
    }
  }
};
