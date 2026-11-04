<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('learning_modules', function (Blueprint $table) {
      $table->text('nama_penyusun')->nullable()->after('ptk_id');
      $table->string('bab')->nullable()->after('materi_pokok');
      $table->json('identifikasi_murid')->nullable();
      $table->json('materi_pelajaran_list')->nullable(); // rename biar tidak bentrok sama materi_pokok
      $table->json('dimensi_profil_lulusan')->nullable();
      $table->text('lintas_disiplin_ilmu')->nullable();
      $table->text('praktik_pedagogis')->nullable();
      $table->text('pemanfaatan_teknologi')->nullable();
      $table->json('asesmen')->nullable();
      $table->json('pengayaan')->nullable();
      $table->json('remedial')->nullable();
      $table->json('refleksi_peserta_didik')->nullable();
      $table->json('refleksi_pendidik')->nullable();
    });

    Schema::table('learning_module_meetings', function (Blueprint $table) {
      $table->string('blok_judul')->nullable()->after('learning_module_id');
      $table->unsignedTinyInteger('blok_urutan')->default(1)->after('blok_judul');
      $table->string('rentang_pertemuan')->nullable()->after('pertemuan_ke');
      $table->json('kegiatan_awal_list')->nullable();
      $table->json('kegiatan_inti_list')->nullable();
      $table->json('kegiatan_penutup_list')->nullable();
    });
  }

  public function down(): void
  {
    Schema::table('learning_modules', function (Blueprint $table) {
      $table->dropColumn([
        'nama_penyusun',
        'bab',
        'identifikasi_murid',
        'materi_pelajaran_list',
        'dimensi_profil_lulusan',
        'lintas_disiplin_ilmu',
        'praktik_pedagogis',
        'pemanfaatan_teknologi',
        'asesmen',
        'pengayaan',
        'remedial',
        'refleksi_peserta_didik',
        'refleksi_pendidik',
      ]);
    });

    Schema::table('learning_module_meetings', function (Blueprint $table) {
      $table->dropColumn([
        'blok_judul',
        'blok_urutan',
        'rentang_pertemuan',
        'kegiatan_awal_list',
        'kegiatan_inti_list',
        'kegiatan_penutup_list',
      ]);
    });
  }
};
