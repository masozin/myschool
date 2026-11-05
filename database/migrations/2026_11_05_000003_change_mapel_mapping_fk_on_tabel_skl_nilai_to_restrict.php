<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * FK skl_nilai.mapel_mapping_id sebelumnya memakai cascadeOnDelete:
   * menghapus satu baris mapping akan ikut menghapus nilai SKL yang
   * menempel padanya — diam-diam. Samakan dengan skhu_nilai yang
   * sudah benar memakai restrictOnDelete.
   */
  public function up(): void
  {
    Schema::table('tabel_skl_nilai', function (Blueprint $table) {
      $table->dropForeign(['mapel_mapping_id']);
      $table->foreign('mapel_mapping_id')
        ->references('id')
        ->on('tabel_mapel_mapping')
        ->restrictOnDelete();
    });
  }

  public function down(): void
  {
    Schema::table('tabel_skl_nilai', function (Blueprint $table) {
      $table->dropForeign(['mapel_mapping_id']);
      $table->foreign('mapel_mapping_id')
        ->references('id')
        ->on('tabel_mapel_mapping')
        ->cascadeOnDelete();
    });
  }
};
