<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  // Refactor skema kelulusan — satu sumber data per angkatan:
  //
  // 1. SKHU mendapat tabel konfigurasi seperti SKL. Tanggal mulai/
  //    selesai ujian, tanggal terbit, dan tempat terbit adalah data
  //    satu angkatan (sekolah + semester), bukan data per siswa —
  //    dipindahkan dari tabel_skhu ke tabel_skhu_konfigurasi.
  //    Nilai yang sudah ada disalin dulu (nilai bukan-kosong pertama
  //    per sekolah + semester) sebelum kolomnya dihapus.
  // 2. tabel_skl tidak lagi menyimpan mode_input dan tanggal_terbit
  //    — keduanya sudah menjadi milik tabel_skl_konfigurasi.
  // 3. tabel_skl_konfigurasi tidak lagi menyimpan prefix_nomor —
  //    awalan nomor cukup ditulis sebagai teks biasa di pola nomor.
  // 4. SKL mendapat tabel template cetak seperti SKHU
  //    (tabel_skl_template); selama belum ada barisnya, cetakan
  //    memakai file bawaan seperti sebelumnya.
  public function up(): void
  {
    // ── 1a. Tabel konfigurasi SKHU ─────────────────────────────────
    if (!Schema::hasTable('tabel_skhu_konfigurasi')) {
      Schema::create('tabel_skhu_konfigurasi', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')
          ->constrained('tabel_sekolah')
          ->restrictOnDelete();
        $table->foreignId('semester_id')
          ->constrained('tabel_semester')
          ->restrictOnDelete();
        $table->date('tanggal_mulai_ujian')->nullable();
        $table->date('tanggal_selesai_ujian')->nullable();
        $table->date('tanggal_terbit')->nullable();
        $table->string('tempat_terbit', 100)->nullable();
        $table->timestamps();

        $table->unique(['sekolah_id', 'semester_id'], 'unique_konfigurasi_skhu');
      });
    }

    // ── 1b. Salin nilai per angkatan dari tabel_skhu ───────────────
    if (Schema::hasColumn('tabel_skhu', 'tanggal_terbit')) {
      $angkatan = DB::table('tabel_skhu')
        ->select('sekolah_id', 'semester_id')
        ->distinct()
        ->get();

      foreach ($angkatan as $a) {
        $pertama = fn(string $kolom) => DB::table('tabel_skhu')
          ->where('sekolah_id', $a->sekolah_id)
          ->where('semester_id', $a->semester_id)
          ->whereNotNull($kolom)
          ->orderBy('id')
          ->value($kolom);

        DB::table('tabel_skhu_konfigurasi')->updateOrInsert(
          ['sekolah_id' => $a->sekolah_id, 'semester_id' => $a->semester_id],
          [
            'tanggal_mulai_ujian'   => $pertama('tanggal_mulai_ujian'),
            'tanggal_selesai_ujian' => $pertama('tanggal_selesai_ujian'),
            'tanggal_terbit'        => $pertama('tanggal_terbit'),
            'tempat_terbit'         => Schema::hasColumn('tabel_skhu', 'tempat_terbit')
              ? $pertama('tempat_terbit')
              : null,
            'created_at'            => now(),
            'updated_at'            => now(),
          ]
        );
      }
    }

    // ── 1c. Hapus kolom per siswa di tabel_skhu ────────────────────
    $kolomSkhu = collect([
      'tanggal_mulai_ujian',
      'tanggal_selesai_ujian',
      'tanggal_terbit',
      'tempat_terbit',
    ])->filter(fn($k) => Schema::hasColumn('tabel_skhu', $k))->all();

    if ($kolomSkhu) {
      Schema::table('tabel_skhu', function (Blueprint $table) use ($kolomSkhu) {
        $table->dropColumn($kolomSkhu);
      });
    }

    // ── 2. tabel_skl: mode & tanggal menjadi milik konfigurasi ─────
    $kolomSkl = collect(['mode_input', 'tanggal_terbit'])
      ->filter(fn($k) => Schema::hasColumn('tabel_skl', $k))->all();

    if ($kolomSkl) {
      Schema::table('tabel_skl', function (Blueprint $table) use ($kolomSkl) {
        $table->dropColumn($kolomSkl);
      });
    }

    // ── 3. tabel_skl_konfigurasi: prefix dihapus ───────────────────
    if (Schema::hasColumn('tabel_skl_konfigurasi', 'prefix_nomor')) {
      Schema::table('tabel_skl_konfigurasi', function (Blueprint $table) {
        $table->dropColumn('prefix_nomor');
      });
    }

    // ── 4. Tabel template cetak SKL ────────────────────────────────
    if (!Schema::hasTable('tabel_skl_template')) {
      Schema::create('tabel_skl_template', function (Blueprint $table) {
        $table->id();
        $table->enum('jenjang', ['SD', 'SMP', 'SMA', 'SMK']);
        $table->foreignId('semester_id')
          ->constrained('tabel_semester')
          ->cascadeOnDelete();
        $table->foreignId('kurikulum_id')
          ->constrained('tabel_kurikulum')
          ->cascadeOnDelete();
        // Nama file di resources/views/templates/skl-template/
        // Contoh: 'skl-2425-sd'
        $table->string('nama_file');
        $table->string('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->unique(['jenjang', 'semester_id']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_skl_template');

    if (!Schema::hasColumn('tabel_skl_konfigurasi', 'prefix_nomor')) {
      Schema::table('tabel_skl_konfigurasi', function (Blueprint $table) {
        $table->string('prefix_nomor')->nullable();
      });
    }

    Schema::table('tabel_skl', function (Blueprint $table) {
      if (!Schema::hasColumn('tabel_skl', 'mode_input')) {
        $table->enum('mode_input', ['jadi', 'manual'])->default('jadi');
      }
      if (!Schema::hasColumn('tabel_skl', 'tanggal_terbit')) {
        $table->date('tanggal_terbit')->nullable();
      }
    });

    Schema::table('tabel_skhu', function (Blueprint $table) {
      if (!Schema::hasColumn('tabel_skhu', 'tanggal_mulai_ujian')) {
        $table->date('tanggal_mulai_ujian')->nullable();
      }
      if (!Schema::hasColumn('tabel_skhu', 'tanggal_selesai_ujian')) {
        $table->date('tanggal_selesai_ujian')->nullable();
      }
      if (!Schema::hasColumn('tabel_skhu', 'tanggal_terbit')) {
        $table->date('tanggal_terbit')->nullable();
      }
      if (!Schema::hasColumn('tabel_skhu', 'tempat_terbit')) {
        $table->string('tempat_terbit', 100)->nullable();
      }
    });

    Schema::dropIfExists('tabel_skhu_konfigurasi');
  }
};
