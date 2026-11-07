<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rename mengikuti pola induk-dulu (konsisten dengan
 * tabel_skl_nilai_komponen / skl_nilai_komponen_id):
 *
 *   tabel_skl_sub_komponen        → tabel_skl_komponen_sub
 *   tabel_skl_nilai_sub_komponen  → tabel_skl_nilai_komponen_sub
 *   kolom sub_komponen_id         → komponen_sub_id
 *
 * Data TIDAK hilang — semua operasi adalah rename.
 *
 * Sekalian: keterangan di tabel sub dibuat nullable. Kolom itu
 * semula NOT NULL tanpa default sementara tidak pernah diisi
 * kode mana pun, sehingga tambah sub-komponen selalu gagal
 * MySQL 1364; tabel induknya (tabel_skl_komponen) memang sudah
 * nullable sejak awal.
 *
 * Catatan urutan (pelajaran MySQL 1553): indeks unik
 * 'unique_nilai_sub' kolom pertamanya skl_nilai_komponen_id,
 * dan MySQL memakainya sebagai indeks pendukung FK kolom itu —
 * jadi unik tidak bisa dijatuhkan selama FK masih ada. Maka
 * KEDUA FK dilepas lebih dulu, baru uniknya. Semua langkah
 * dijaga (guard) agar migrasi aman dijalankan ulang dari
 * keadaan gagal sebagian: percobaan pertama sempat melepas
 * satu FK sebelum berhenti di 1553.
 */
return new class extends Migration
{
  /** Nama-nama constraint FK pada tabel lama. */
  private function fkLama(): array
  {
    return [
      'tabel_skl_nilai_sub_komponen_skl_nilai_komponen_id_foreign',
      'tabel_skl_nilai_sub_komponen_sub_komponen_id_foreign',
    ];
  }

  /** Nama-nama constraint FK pada tabel baru. */
  private function fkBaru(): array
  {
    return [
      'tabel_skl_nilai_komponen_sub_skl_nilai_komponen_id_foreign',
      'tabel_skl_nilai_komponen_sub_komponen_sub_id_foreign',
    ];
  }

  /** Jatuhkan FK yang masih ada (berdasarkan nama constraint). */
  private function lepasFk(string $tabel, array $namaFk): void
  {
    if (!Schema::hasTable($tabel)) {
      return;
    }

    $ada = collect(Schema::getForeignKeys($tabel))->pluck('name');

    Schema::table($tabel, function (Blueprint $table) use ($ada, $namaFk) {
      foreach ($namaFk as $fk) {
        if ($ada->contains($fk)) {
          $table->dropForeign($fk);
        }
      }
    });
  }

  /** Pasang FK bila belum ada FK pada kolom tersebut. */
  private function pasangFk(string $tabel, string $kolom, string $tabelTujuan): void
  {
    $kolomFk = collect(Schema::getForeignKeys($tabel))
      ->flatMap(fn($fk) => $fk['columns']);

    if ($kolomFk->contains($kolom)) {
      return;
    }

    Schema::table($tabel, function (Blueprint $table) use ($kolom, $tabelTujuan) {
      $table->foreign($kolom)
        ->references('id')
        ->on($tabelTujuan)
        ->cascadeOnDelete();
    });
  }

  public function up(): void
  {
    $lama = 'tabel_skl_nilai_sub_komponen';
    $baru = 'tabel_skl_nilai_komponen_sub';

    // 1) Lepas KEDUA FK lebih dulu (1553), baru indeks uniknya
    $this->lepasFk($lama, $this->fkLama());

    if (Schema::hasTable($lama) && Schema::hasIndex($lama, 'unique_nilai_sub')) {
      Schema::table($lama, function (Blueprint $table) {
        $table->dropUnique('unique_nilai_sub');
      });
    }

    // 2) Rename tabel
    if (Schema::hasTable('tabel_skl_sub_komponen')) {
      Schema::rename('tabel_skl_sub_komponen', 'tabel_skl_komponen_sub');
    }
    if (Schema::hasTable($lama)) {
      Schema::rename($lama, $baru);
    }

    // 3) Rename kolom, lalu pasang kembali FK & unique
    if (Schema::hasColumn($baru, 'sub_komponen_id')) {
      Schema::table($baru, function (Blueprint $table) {
        $table->renameColumn('sub_komponen_id', 'komponen_sub_id');
      });
    }

    $this->pasangFk($baru, 'skl_nilai_komponen_id', 'tabel_skl_nilai_komponen');
    $this->pasangFk($baru, 'komponen_sub_id', 'tabel_skl_komponen_sub');

    if (Schema::hasTable($baru) && !Schema::hasIndex($baru, 'unique_nilai_komponen_sub')) {
      Schema::table($baru, function (Blueprint $table) {
        $table->unique(
          ['skl_nilai_komponen_id', 'komponen_sub_id'],
          'unique_nilai_komponen_sub'
        );
      });
    }

    // 4) keterangan boleh kosong
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::table('tabel_skl_komponen_sub', function (Blueprint $table) {
        $table->string('keterangan', 100)->nullable()->change();
      });
    }
  }

  public function down(): void
  {
    $lama = 'tabel_skl_nilai_sub_komponen';
    $baru = 'tabel_skl_nilai_komponen_sub';

    // keterangan kembali NOT NULL — gagal bila sudah ada baris
    // berketerangan kosong, memang disengaja agar tidak senyap.
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::table('tabel_skl_komponen_sub', function (Blueprint $table) {
        $table->string('keterangan', 100)->nullable(false)->change();
      });
    }

    // Lepas kedua FK (nama baru) lebih dulu, baru uniknya
    $this->lepasFk($baru, $this->fkBaru());

    if (Schema::hasTable($baru) && Schema::hasIndex($baru, 'unique_nilai_komponen_sub')) {
      Schema::table($baru, function (Blueprint $table) {
        $table->dropUnique('unique_nilai_komponen_sub');
      });
    }

    // Kolom kembali ke nama lama, pasang lagi FK & unique lama
    // (tabel tujuan masih bernama baru pada titik ini)
    if (Schema::hasColumn($baru, 'komponen_sub_id')) {
      Schema::table($baru, function (Blueprint $table) {
        $table->renameColumn('komponen_sub_id', 'sub_komponen_id');
      });
    }

    $this->pasangFk($baru, 'skl_nilai_komponen_id', 'tabel_skl_nilai_komponen');
    $this->pasangFk($baru, 'sub_komponen_id', 'tabel_skl_komponen_sub');

    if (Schema::hasTable($baru) && !Schema::hasIndex($baru, 'unique_nilai_sub')) {
      Schema::table($baru, function (Blueprint $table) {
        $table->unique(
          ['skl_nilai_komponen_id', 'sub_komponen_id'],
          'unique_nilai_sub'
        );
      });
    }

    // Terakhir kembalikan nama kedua tabel
    if (Schema::hasTable($baru)) {
      Schema::rename($baru, $lama);
    }
    if (Schema::hasTable('tabel_skl_komponen_sub')) {
      Schema::rename('tabel_skl_komponen_sub', 'tabel_skl_sub_komponen');
    }
  }
};
