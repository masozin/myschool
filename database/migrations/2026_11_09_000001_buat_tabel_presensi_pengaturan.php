<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Pengaturan presensi per sekolah. Saat ini berisi batas
   * koreksi presensi (hari) untuk Guru wali kelas — bawaan 7
   * hari, dapat diubah Operator Sekolah dari halaman Presensi.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_presensi_pengaturan')) {
      Schema::create('tabel_presensi_pengaturan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')
          ->unique()
          ->constrained('tabel_sekolah')
          ->cascadeOnDelete();
        $table->unsignedSmallInteger('batas_koreksi_hari')->default(7);
        $table->timestamps();
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_presensi_pengaturan');
  }
};
