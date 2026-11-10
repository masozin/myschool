<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Fondasi modul Jadwal Pelajaran (fase 1):
   *
   * - tabel_jadwal_pengaturan: kerangka waktu per sekolah+semester
   *   (JSON per hari: jumlah jam, jam mulai, menit per jam, jeda
   *   istirahat). Jam tampil dihitung dari kerangka ini.
   * - tabel_pengampu: penetapan guru pengampu per rombel+mapel+
   *   semester (lapisan di atas tabel_mapel_mapping yang memang
   *   tidak menyimpan guru).
   * - tabel_jadwal: isi kotak hari x jam per rombel; unik
   *   (rombel, semester, hari, jam_ke) sehingga satu kotak tidak
   *   bisa terisi dua pelajaran.
   *
   * Migrasi dijaga hasTable agar aman diulang dari keadaan
   * setengah jadi.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_jadwal_pengaturan')) {
      Schema::create('tabel_jadwal_pengaturan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('semester_id')->constrained('tabel_semester')->cascadeOnDelete();
        $table->json('kerangka')->nullable();
        $table->timestamps();

        $table->unique(['sekolah_id', 'semester_id'], 'uniq_jadwal_pengaturan');
      });
    }

    if (!Schema::hasTable('tabel_pengampu')) {
      Schema::create('tabel_pengampu', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('semester_id')->constrained('tabel_semester')->cascadeOnDelete();
        $table->foreignId('rombel_id')->constrained('tabel_rombel')->cascadeOnDelete();
        $table->foreignId('mapel_mapping_id')->constrained('tabel_mapel_mapping')->cascadeOnDelete();
        $table->foreignId('ptk_id')->constrained('tabel_ptk')->cascadeOnDelete();
        // Cadangan untuk indikator target JP/minggu (belum dipakai
        // di fase 1 — keputusan D6: target ditunda).
        $table->unsignedTinyInteger('target_jam')->nullable();
        $table->timestamps();

        $table->unique(['rombel_id', 'mapel_mapping_id', 'semester_id'], 'uniq_pengampu_rombel_mapel');
        $table->index('ptk_id');
      });
    }

    if (!Schema::hasTable('tabel_jadwal')) {
      Schema::create('tabel_jadwal', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('semester_id')->constrained('tabel_semester')->cascadeOnDelete();
        $table->foreignId('rombel_id')->constrained('tabel_rombel')->cascadeOnDelete();
        $table->foreignId('pengampu_id')->constrained('tabel_pengampu')->cascadeOnDelete();
        $table->unsignedTinyInteger('hari')->comment('1=Senin .. 6=Sabtu');
        $table->unsignedTinyInteger('jam_ke');
        $table->timestamps();

        $table->unique(['rombel_id', 'semester_id', 'hari', 'jam_ke'], 'uniq_jadwal_kotak');
        $table->index(['semester_id', 'hari', 'jam_ke'], 'idx_jadwal_waktu');
        $table->index('pengampu_id');
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_jadwal');
    Schema::dropIfExists('tabel_pengampu');
    Schema::dropIfExists('tabel_jadwal_pengaturan');
  }
};
