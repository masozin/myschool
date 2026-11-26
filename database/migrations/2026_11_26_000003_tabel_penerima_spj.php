<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * SPJ Fase 2 — penerima per grup transaksi BKU.
 *
 * - tabel_penerima: induk penerima NON-PTK per sekolah
 *   (narasumber luar, pelatih ekstrakurikuler, komite, …)
 *   yang datanya tidak ada di tabel_ptk. Dikelola dari
 *   editor penerima di halaman BKU.
 * - bos_spj_penerima: penerima yang melekat pada satu
 *   grup transaksi BKU (pengelompokan yang sama dengan
 *   kwitansi Fase 1: per nota / nomor bukti — kuncinya
 *   teks grup_kunci dari KelolaBosController). Isinya
 *   SNAPSHOT: disalin dari PTK / tabel_penerima saat
 *   ditambahkan, jadi perubahan data induk belakangan
 *   tidak mengubah dokumen SPJ yang sudah dibuat.
 *   ptk_id / penerima_id hanya penanda asal (nullOnDelete)
 *   agar duplikat dari sumber yang sama bisa dicegah.
 *
 * Ini tabel KERJA milik pengguna di myschool — bukan
 * cermin ARKAS, tidak disentuh agen sinkron.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('tabel_penerima')) {
      Schema::create('tabel_penerima', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('nama');
        $table->string('nik', 16)->nullable();
        $table->string('nip', 18)->nullable();
        $table->string('pangkat_gol', 100)->nullable();
        $table->text('alamat')->nullable();
        $table->string('no_rekening', 30)->nullable();
        $table->string('nama_bank', 100)->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('bos_spj_penerima')) {
      Schema::create('bos_spj_penerima', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('grup_kunci', 120);
        $table->foreignId('ptk_id')->nullable()->constrained('tabel_ptk')->nullOnDelete();
        $table->foreignId('penerima_id')->nullable()->constrained('tabel_penerima')->nullOnDelete();
        $table->string('nama');
        $table->string('nip', 18)->nullable();
        $table->string('pangkat_gol', 100)->nullable();
        $table->string('nik', 16)->nullable();
        $table->string('no_rekening', 30)->nullable();
        $table->string('nama_bank', 100)->nullable();
        $table->decimal('volume', 10, 2)->default(1);
        $table->decimal('satuan', 15, 2)->default(0);
        $table->decimal('pph_persen', 5, 2)->default(0);
        $table->unsignedInteger('urutan')->default(0);
        $table->timestamps();
        $table->index(['sekolah_id', 'grup_kunci']);
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_spj_penerima');
    Schema::dropIfExists('tabel_penerima');
  }
};
