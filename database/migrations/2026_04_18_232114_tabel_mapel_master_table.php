<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('tabel_mapel_kelompok', function (Blueprint $table) {
      $table->id();
      $table->string('kode', 20)->nullable()->unique();
      $table->string('nama')->comment('Contoh: Muatan Nasional, Muatan Kewilayahan, Peminatan');
      $table->string('keterangan')->nullable();
      $table->enum('jenjang_scope', ['SD', 'SMP', 'SMA', 'SMK'])
        ->nullable()      // jika NULL berarti berlaku untuk semua jenjang
        ->comment('Jenjang yang relevan, semua = lintas jenjang');
      $table->unsignedTinyInteger('urutan')->default(1);
      $table->timestamps();
    });

    // ── Tabel master mapel ───────────────────────────────────────────────
    Schema::create('tabel_mapel_master', function (Blueprint $table) {
      $table->id();
      $table->string('kode', 30)->nullable()->unique();
      $table->foreignId('mapel_kelompok_id')
        ->constrained('tabel_mapel_kelompok')
        ->restrictOnDelete();
      $table->string('nama')->comment('Nama mata pelajaran');
      $table->string('nama_sub_mapel')->nullable();
      $table->string('nama_singkat')->nullable();
      $table->boolean('is_aktif')->default(true);
      $table->foreignId('sekolah_id')
        ->nullable()
        ->constrained('tabel_sekolah')
        ->nullOnDelete()
        ->comment('Untuk mapel lokal/mulok sekolah tertentu');
      $table->timestamps();
      $table->softDeletes();
    });

    // ── Pivot: mapel <-> kurikulum ───────────────────────────────────────
    Schema::create('tabel_mapel_kurikulum', function (Blueprint $table) {
      $table->id();
      $table->foreignId('mapel_master_id')->constrained('tabel_mapel_master')->restrictOnDelete();
      $table->foreignId('kurikulum_id')
        ->nullable()                      // jika NULL berarti berlaku untuk semua
        ->constrained('tabel_kurikulum')
        ->restrictOnDelete();
      $table->unique(['mapel_master_id', 'kurikulum_id']);
    });

    // ── Pivot: mapel <-> jenjang ─────────────────────────────────────────
    Schema::create('tabel_mapel_jenjang', function (Blueprint $table) {
      $table->id();
      $table->foreignId('mapel_master_id')->constrained('tabel_mapel_master')->restrictOnDelete();
      $table->enum('jenjang', ['SD', 'MI', 'SMP', 'MTs', 'SMA', 'MA', 'SMK', 'MAK']);
      $table->unsignedTinyInteger('kelas_mulai')->nullable()->comment('Kelas awal, misal: 1, 7, 10');
      $table->unsignedTinyInteger('kelas_selesai')->nullable()->comment('Kelas akhir, misal: 6, 9, 12');
      $table->unique(['mapel_master_id', 'jenjang']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_mapel_kelompok');
    Schema::dropIfExists('tabel_mapel_master');
    Schema::dropIfExists('tabel_mapel_kurikulum');
    Schema::dropIfExists('tabel_mapel_jenjang');
  }
};
