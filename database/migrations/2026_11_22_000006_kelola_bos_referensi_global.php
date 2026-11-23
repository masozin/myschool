<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 1.13 — tabel referensi Kelola BOS menjadi GLOBAL.
 *
 * Enam tabel cermin referensi ARKAS (bos_ref_bku,
 * bos_ref_periode, bos_ref_satuan, bos_ref_kode,
 * bos_ref_rekening, bos_ref_acuan_barang) isinya data
 * induk nasional yang sama untuk semua sekolah, jadi
 * salinan per sekolah hanya menggandakan penyimpanan
 * dan unggahan sinkron. Mulai fase ini keenamnya
 * disimpan SATU salinan bersama tanpa sekolah_id;
 * tabel transaksi (kas umum, nota, rapbs, periode
 * rapbs, anggaran, penjab) dan tabel kerja (alias,
 * token, log) tetap milik sekolah.
 *
 * Langkah per tabel:
 *  (a) dedupe — satu baris per id_arkas; yang bertahan
 *      adalah baris dengan arkas_last_update terbesar
 *      (seri: id terbesar);
 *  (b) khusus bos_ref_kode: hapus baris id_kode NULL —
 *      sisa Fase 1 berkunci format lama yang tidak
 *      pernah tampil di halaman mana pun (sekaligus
 *      menyembuhkan selisih jumlah yang memaksa agen
 *      mengirim ulang ref-kode secara penuh);
 *  (c) buang foreign key + kolom sekolah_id dan indeks
 *      yang memuatnya, lalu buat unique(id_arkas) dan
 *      indeks pengganti tanpa sekolah_id.
 *
 * down() bersifat best-effort: kolom sekolah_id
 * ditambahkan kembali sebagai nullable, tetapi baris
 * yang terlanjur tergabung tidak bisa dipecah lagi
 * per sekolah — penggabungan ini satu arah.
 */
return new class extends Migration
{
  /** Tabel referensi yang diglobalkan. */
  private array $tabelRef = [
    'bos_ref_bku',
    'bos_ref_periode',
    'bos_ref_satuan',
    'bos_ref_kode',
    'bos_ref_rekening',
    'bos_ref_acuan_barang',
  ];

  /** Indeks pengganti (tanpa sekolah_id) per tabel. */
  private array $indeksBaru = [
    'bos_ref_bku' => [['id_ref_bku']],
    'bos_ref_periode' => [['id_periode']],
    'bos_ref_satuan' => [],
    'bos_ref_kode' => [['id_ref_kode', 'tahun']],
    'bos_ref_rekening' => [['kode_rekening']],
    'bos_ref_acuan_barang' => [['nama_barang'], ['kode_rekening'], ['tahun']],
  ];

  public function up(): void
  {
    foreach ($this->tabelRef as $tabel) {
      if (!Schema::hasTable($tabel)) {
        continue;
      }

      if (Schema::hasColumn($tabel, 'sekolah_id')) {
        $this->dedupe($tabel);

        if ($tabel === 'bos_ref_kode') {
          DB::table($tabel)->whereNull('id_kode')->delete();
        }

        // (1) Foreign key DULU. MySQL menolak membuang indeks
        // yang masih menopang sebuah FK (error 1553 "Cannot
        // drop index ... needed in a foreign key constraint")
        // — versi awal migrasi ini membuang indeks lebih dulu
        // dan gagal di bos_ref_bku pada MySQL Mark 2026-11-23.
        foreach (Schema::getForeignKeys($tabel) as $fk) {
          if (in_array('sekolah_id', $fk['columns'] ?? [], true)) {
            $namaFk = $fk['name'] ?? null;
            Schema::table($tabel, function (Blueprint $t) use ($namaFk) {
              if ($namaFk) {
                $t->dropForeign($namaFk);
              } else {
                $t->dropForeign(['sekolah_id']);
              }
            });
          }
        }

        // (2) Buang semua indeks (termasuk unik) yang memuat
        // sekolah_id, apa pun namanya.
        foreach (Schema::getIndexes($tabel) as $indeks) {
          if (($indeks['type'] ?? null) === 'primary') {
            continue;
          }
          if (in_array('sekolah_id', $indeks['columns'] ?? [], true)) {
            $nama = $indeks['name'];
            Schema::table($tabel, function (Blueprint $t) use ($indeks, $nama) {
              if (!empty($indeks['unique'])) {
                $t->dropUnique($nama);
              } else {
                $t->dropIndex($nama);
              }
            });
          }
        }

        // (3) Terakhir kolomnya.
        Schema::table($tabel, function (Blueprint $t) {
          $t->dropColumn('sekolah_id');
        });
      }

      // Langkah penutup di luar blok sekolah_id dan selalu
      // diperiksa keberadaannya: bila migrate sebelumnya
      // berhenti di tengah satu tabel (kolom sudah terbuang
      // tetapi unik/indeks baru belum dibuat), menjalankan
      // ulang migrasi ini melengkapinya, bukan mengulang dari
      // nol atau gagal karena objek sudah ada.
      if (!$this->punyaIndeks($tabel, ['id_arkas'], true)) {
        Schema::table($tabel, function (Blueprint $t) {
          $t->unique('id_arkas');
        });
      }
      foreach ($this->indeksBaru[$tabel] as $kolom) {
        if (!$this->punyaIndeks($tabel, $kolom, false)) {
          Schema::table($tabel, function (Blueprint $t) use ($kolom) {
            $t->index($kolom);
          });
        }
      }
    }
  }

  /** Sudah adakah indeks dengan kolom persis ini? */
  private function punyaIndeks(string $tabel, array $kolom, bool $harusUnik): bool
  {
    foreach (Schema::getIndexes($tabel) as $indeks) {
      if (($indeks['columns'] ?? []) !== $kolom) {
        continue;
      }
      if ($harusUnik && empty($indeks['unique'])) {
        continue;
      }
      return true;
    }
    return false;
  }

  public function down(): void
  {
    // Best-effort: kembalikan kolom sekolah_id (nullable,
    // tanpa data) dan indeks gabungan lamanya. Baris
    // referensi yang sudah tergabung global TIDAK
    // dipecah kembali per sekolah.
    foreach ($this->tabelRef as $tabel) {
      if (!Schema::hasTable($tabel) || Schema::hasColumn($tabel, 'sekolah_id')) {
        continue;
      }
      Schema::table($tabel, function (Blueprint $t) {
        $t->dropUnique(['id_arkas']);
      });
      Schema::table($tabel, function (Blueprint $t) {
        $t->foreignId('sekolah_id')->nullable()->constrained('tabel_sekolah')->cascadeOnDelete();
        $t->unique(['sekolah_id', 'id_arkas']);
      });
    }
  }

  /**
   * Sisakan satu baris per id_arkas: yang menang adalah
   * arkas_last_update terbesar, seri dipecahkan oleh id
   * terbesar. Tabel tanpa kolom arkas_last_update
   * (bos_ref_satuan) cukup memakai id terbesar.
   */
  private function dedupe(string $tabel): void
  {
    $punyaLu = Schema::hasColumn($tabel, 'arkas_last_update');

    $ganda = DB::table($tabel)
      ->select('id_arkas')
      ->groupBy('id_arkas')
      ->havingRaw('COUNT(*) > 1')
      ->pluck('id_arkas');

    foreach ($ganda->chunk(200) as $potongan) {
      foreach ($potongan as $idArkas) {
        $query = DB::table($tabel)->where('id_arkas', $idArkas);
        if ($punyaLu) {
          $query->orderByDesc('arkas_last_update');
        }
        $ids = $query->orderByDesc('id')->pluck('id');
        $buang = $ids->slice(1)->all();
        if ($buang) {
          DB::table($tabel)->whereIn('id', $buang)->delete();
        }
      }
    }
  }
};
