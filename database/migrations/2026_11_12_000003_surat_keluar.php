<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengelola Surat Keluar: agenda, kategori, berkas arsip, dan
 * pengaturan penomoran per sekolah. Semua pembuatan tabel
 * dijaga hasTable agar aman diulang dari keadaan setengah jalan.
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('tabel_surat_kategori')) {
      Schema::create('tabel_surat_kategori', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('kode', 20);
        $table->string('nama', 100);
        $table->unsignedInteger('urutan')->default(0);
        $table->timestamps();
        $table->unique(['sekolah_id', 'kode']);
      });
    }

    if (!Schema::hasTable('tabel_surat_pengaturan')) {
      Schema::create('tabel_surat_pengaturan', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->unique()->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('format_nomor', 150)->default('{no:3}/{kode}/{bulan_romawi}/{tahun}');
        $table->unsignedInteger('nomor_terakhir')->default(0);
        $table->unsignedSmallInteger('tahun_berjalan')->nullable();
        $table->timestamps();
      });
    }

    if (!Schema::hasTable('tabel_surat_keluar')) {
      Schema::create('tabel_surat_keluar', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->foreignId('kategori_id')->nullable()->constrained('tabel_surat_kategori')->nullOnDelete();
        $table->unsignedInteger('nomor_urut')->nullable();
        $table->string('nomor_surat', 150)->nullable();
        $table->boolean('nomor_manual')->default(false);
        $table->date('tanggal_surat');
        $table->string('tujuan', 200);
        $table->string('perihal', 250);
        $table->text('isi_ringkas')->nullable();
        $table->string('lampiran', 50)->nullable();
        $table->foreignId('penandatangan_ptk_id')->nullable()->constrained('tabel_ptk')->nullOnDelete();
        $table->enum('sifat', ['Biasa', 'Penting', 'Segera', 'Rahasia'])->default('Biasa');
        $table->enum('status', ['draft', 'terbit', 'batal'])->default('draft');
        $table->foreignId('dibuat_oleh')->nullable()->constrained('users')->nullOnDelete();
        $table->text('catatan')->nullable();
        $table->timestamps();
        $table->index(['sekolah_id', 'tanggal_surat']);
        $table->index(['sekolah_id', 'status']);
      });
    }

    if (!Schema::hasTable('tabel_surat_berkas')) {
      Schema::create('tabel_surat_berkas', function (Blueprint $table) {
        $table->id();
        $table->foreignId('surat_id')->constrained('tabel_surat_keluar')->cascadeOnDelete();
        $table->enum('jenis', ['arsip', 'lampiran'])->default('lampiran');
        $table->string('nama_asli', 200);
        $table->string('path', 250);
        $table->string('mime', 100)->nullable();
        $table->unsignedBigInteger('ukuran')->default(0);
        $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_surat_berkas');
    Schema::dropIfExists('tabel_surat_keluar');
    Schema::dropIfExists('tabel_surat_pengaturan');
    Schema::dropIfExists('tabel_surat_kategori');
  }
};
