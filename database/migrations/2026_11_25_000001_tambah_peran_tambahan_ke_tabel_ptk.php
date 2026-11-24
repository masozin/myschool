<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Peran tambahan PTK — fondasi multi-role (satu user bisa memegang
 * lebih dari satu peran, mis. Guru sekaligus Operator Sekolah atau
 * Bendahara). Jabatan utama tetap tunggal di kolom jabatan; peran
 * di luar jabatan utama dicatat di sini sebagai array JSON nama role
 * Spatie, lalu diterapkan ke akun user saat akun dibuat / PTK diedit.
 * PTK lama tidak berubah: kolom ini kosong (null) bagi mereka.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_ptk', 'peran_tambahan')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->json('peran_tambahan')->nullable()->after('jabatan');
      });
    }
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'peran_tambahan')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('peran_tambahan');
      });
    }
  }
};
