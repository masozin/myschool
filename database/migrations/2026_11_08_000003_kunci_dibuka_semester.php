<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda buka-kunci manual untuk kunci semester menyeluruh.
 *
 * Semester yang bukan semester aktif terkunci untuk perubahan
 * data (lihat App\Http\Middleware\CheckSemesterLock). Admin
 * dapat membuka kunci satu semester bila memang diperlukan —
 * penanda itu disimpan pada baris semesternya sendiri.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_semester', 'kunci_dibuka')) {
      Schema::table('tabel_semester', function (Blueprint $table) {
        $table->boolean('kunci_dibuka')->default(false);
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_semester', 'kunci_dibuka')) {
      Schema::table('tabel_semester', function (Blueprint $table) {
        $table->dropColumn('kunci_dibuka');
      });
    }
  }
};
