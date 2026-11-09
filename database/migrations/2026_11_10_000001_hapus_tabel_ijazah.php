<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Menghapus tabel-tabel modul Ijazah. Modul ini kloningan SKL
   * yang tidak pernah dipakai (tidak ada rute/menu) dan kodenya
   * sudah dihapus. Tabel hanya dihapus bila BENAR-BENAR KOSONG —
   * bila suatu tabel ternyata berisi data, migrasi berhenti
   * dengan pesan jelas dan tidak menghapus apa pun, supaya tidak
   * ada data yang hilang tanpa disadari. Urutan penghapusan dari
   * tabel anak ke tabel induk, dengan pemeriksaan FK dimatikan
   * selama proses.
   */
  public function up(): void
  {
    $daftarTabel = [
      'tabel_ijazah_nilai_sub_komponen',
      'tabel_ijazah_nilai_komponen',
      'tabel_ijazah_nilai',
      'tabel_ijazah_sub_komponen',
      'tabel_ijazah_komponen',
      'tabel_ijazah_konfigurasi',
      'tabel_ijazah',
    ];

    Schema::disableForeignKeyConstraints();

    try {
      foreach ($daftarTabel as $tabel) {
        if (!Schema::hasTable($tabel)) {
          continue;
        }
        if (DB::table($tabel)->count() > 0) {
          throw new \RuntimeException(
            "Migrasi hapus ijazah berhenti: tabel {$tabel} ternyata berisi data. "
            . 'Tidak ada tabel yang dihapus. Periksa dulu isi tabel tersebut.'
          );
        }
        Schema::drop($tabel);
      }
    } finally {
      Schema::enableForeignKeyConstraints();
    }
  }

  /**
   * Tidak dapat dibalikkan: skema lama sengaja tidak dibuat
   * ulang karena modulnya sudah dihapus permanen dan tabelnya
   * kosong saat dihapus.
   */
  public function down(): void
  {
    // no-op
  }
};
