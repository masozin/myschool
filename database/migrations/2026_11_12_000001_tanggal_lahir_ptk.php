<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tanggal lahir PTK — bahan fitur "info hari ini"
 * (kartu ulang tahun di dashboard web & aplikasi mobile).
 *
 * Isi awal otomatis: NIP PNS 18 digit memuat tanggal lahir
 * pada 8 digit pertamanya (YYYYMMDD). PTK yang NIP-nya sah
 * sebagai tanggal diisi dari situ; sisanya diisi manual
 * lewat formulir PTK. Hanya mengisi yang masih kosong.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasColumn('tabel_ptk', 'tanggal_lahir')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->date('tanggal_lahir')->nullable()->after('jenis_kelamin');
      });
    }

    $tahunMin = 1940;
    $tahunMaks = (int) date('Y') - 17;

    DB::table('tabel_ptk')
      ->whereNotNull('nip')
      ->whereNull('tanggal_lahir')
      ->orderBy('id')
      ->each(function ($baris) use ($tahunMin, $tahunMaks) {
        $nip = preg_replace('/\D/', '', (string) $baris->nip);
        if (strlen($nip) !== 18) {
          return;
        }
        $tahun = (int) substr($nip, 0, 4);
        $bulan = (int) substr($nip, 4, 2);
        $hari = (int) substr($nip, 6, 2);
        if ($tahun < $tahunMin || $tahun > $tahunMaks) {
          return;
        }
        if (!checkdate($bulan, $hari, $tahun)) {
          return;
        }
        DB::table('tabel_ptk')
          ->where('id', $baris->id)
          ->update([
            'tanggal_lahir' => sprintf('%04d-%02d-%02d', $tahun, $bulan, $hari),
          ]);
      });
  }

  public function down(): void
  {
    if (Schema::hasColumn('tabel_ptk', 'tanggal_lahir')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->dropColumn('tanggal_lahir');
      });
    }
  }
};
