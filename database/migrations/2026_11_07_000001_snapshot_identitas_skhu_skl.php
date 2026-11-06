<?php

use App\Models\Skhu;
use App\Models\Skl;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  // Snapshot identitas siswa untuk dokumen kelulusan. Selama draft,
  // cetakan SKHU/SKL membaca data induk siswa secara live; saat
  // dokumen disahkan (SKHU final, SKL disetujui) identitas yang
  // tercetak saat itu disalin ke kolom snapshot_* dan cetakan
  // membacanya dari sana — perubahan data induk sesudahnya tidak
  // lagi mengubah dokumen yang sudah terbit. Batal pengesahan
  // mengosongkan snapshot (kembali mengikuti data induk).
  private array $kolom = [
    'snapshot_nama'          => 'string',
    'snapshot_nis'           => 'string',
    'snapshot_nisn'          => 'string',
    'snapshot_tempat_lahir'  => 'string',
    'snapshot_tgl_lahir'     => 'date',
    'snapshot_nama_ayah'     => 'string',
  ];

  public function up(): void
  {
    foreach (['tabel_skhu', 'tabel_skl'] as $tabel) {
      foreach ($this->kolom as $nama => $tipe) {
        if (!Schema::hasColumn($tabel, $nama)) {
          Schema::table($tabel, function (Blueprint $table) use ($nama, $tipe) {
            $table->$tipe($nama)->nullable();
          });
        }
      }
    }

    // Backfill dokumen yang SUDAH terbit sebelum fitur ini ada:
    // bekukan identitasnya dari data induk saat ini (perkiraan
    // terbaik atas isi dokumen saat diterbitkan).
    Skhu::where('status', 'final')
      ->with(['siswa.registrasiTerbaru'])
      ->chunkById(100, function ($rows) {
        foreach ($rows as $skhu) {
          $skhu->isiSnapshotIdentitas();
          $skhu->saveQuietly();
        }
      });

    Skl::where('status', 'disetujui')
      ->with(['siswa.registrasiTerbaru'])
      ->chunkById(100, function ($rows) {
        foreach ($rows as $skl) {
          $skl->isiSnapshotIdentitas();
          $skl->saveQuietly();
        }
      });
  }

  public function down(): void
  {
    foreach (['tabel_skhu', 'tabel_skl'] as $tabel) {
      $ada = array_filter(
        array_keys($this->kolom),
        fn($nama) => Schema::hasColumn($tabel, $nama)
      );

      if ($ada) {
        Schema::table($tabel, function (Blueprint $table) use ($ada) {
          $table->dropColumn(array_values($ada));
        });
      }
    }
  }
};
