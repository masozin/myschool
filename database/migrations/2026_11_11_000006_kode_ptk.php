<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Kode guru pada PTK (mis. "GR-01" atau inisial "BS").
   *
   * Dipakai sebagai label ringkas guru, terutama pada template
   * Excel impor jadwal: kolom kode guru di samping tabel jadwal
   * terisi otomatis dari pengampu, dan kode yang muncul dua
   * kali dalam satu baris (bentrok) ditandai merah. Boleh
   * kosong — template memakai inisial nama sebagai cadangan.
   *
   * Dijaga hasColumn agar aman diulang.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_ptk')) {
      return;
    }

    if (!Schema::hasColumn('tabel_ptk', 'kode')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->string('kode', 20)->nullable()->after('nip');
      });
    }
  }

  public function down(): void
  {
    if (
      Schema::hasTable('tabel_ptk')
      && Schema::hasColumn('tabel_ptk', 'kode')
    ) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('kode');
      });
    }
  }
};
