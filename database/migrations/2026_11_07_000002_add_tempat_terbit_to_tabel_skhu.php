<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  // Tempat penerbitan SKHU (titimangsa tanda tangan). Selama ini
  // tempat tercetak selalu nama desa dari detail sekolah; kolom ini
  // memberi gantinya bila diisi — diatur massal dari halaman
  // Tanggal & Penomoran atau per siswa dari form SKHU. Kosong
  // berarti cetakan tetap memakai nama desa seperti sebelumnya.
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_skhu', 'tempat_terbit')) {
      Schema::table('tabel_skhu', function (Blueprint $table) {
        $table->string('tempat_terbit', 100)->nullable()->after('tanggal_terbit');
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_skhu', 'tempat_terbit')) {
      Schema::table('tabel_skhu', function (Blueprint $table) {
        $table->dropColumn('tempat_terbit');
      });
    }
  }
};
