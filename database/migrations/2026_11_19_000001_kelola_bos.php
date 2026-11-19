<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kelola BOS (fitur: kelola-bos) — tabel cermin data ARKAS.
 *
 * Data di tabel bos_* adalah SALINAN read-only dari database
 * ARKAS sekolah, dikirim agen sinkron (POST /api/bos/sinkron/*)
 * dan di-upsert berdasarkan (sekolah_id, id_arkas). Baris yang
 * di ARKAS ter-soft_delete tetap disimpan dengan flag
 * soft_delete = true; halaman-halaman menyaringnya keluar.
 * Pengeditan dokumen (kuitansi, rekap) TIDAK menyentuh tabel
 * ini — itu lapisan kerja Fase 2 di atas cermin.
 *
 * Semua pembuatan tabel dijaga hasTable agar aman diulang
 * dari keadaan setengah jalan (pola migrasi Surat Keluar).
 */
return new class extends Migration
{
  public function up(): void
  {
    if (!Schema::hasTable('bos_kas_umum')) {
      Schema::create('bos_kas_umum', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('id_kas_nota', 30)->nullable();
        $table->string('id_rapbs_periode', 30)->nullable();
        $table->string('id_anggaran_arkas', 30)->nullable();
        $table->string('kode_rekening', 20)->nullable();
        $table->unsignedInteger('id_ref_bku');
        $table->dateTime('tanggal_transaksi');
        $table->string('no_bukti', 30)->nullable();
        $table->string('uraian', 500);
        $table->string('uraian_pajak', 255)->nullable();
        $table->decimal('saldo', 15, 2);
        $table->char('status_bku', 1)->nullable();
        $table->decimal('volume', 12, 2)->nullable();
        $table->boolean('is_ppn')->default(false);
        $table->boolean('is_pph_21')->default(false);
        $table->boolean('is_pph_22')->default(false);
        $table->boolean('is_pph_23')->default(false);
        $table->boolean('is_pph_4')->default(false);
        $table->boolean('is_sspd')->default(false);
        $table->boolean('is_lock')->nullable();
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'tanggal_transaksi']);
        $table->index(['sekolah_id', 'id_ref_bku']);
        $table->index(['sekolah_id', 'id_kas_nota']);
      });
    }

    if (!Schema::hasTable('bos_kas_nota')) {
      Schema::create('bos_kas_nota', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('no_bukti', 30)->nullable();
        $table->date('tanggal_nota')->nullable();
        $table->string('no_nota', 255)->nullable();
        $table->string('nama_toko', 255)->nullable();
        $table->text('alamat_toko')->nullable();
        $table->string('no_telp', 25)->nullable();
        $table->boolean('is_badan_usaha')->nullable();
        $table->string('npwp', 20)->nullable();
        $table->decimal('total', 15, 2)->nullable();
        $table->string('kode_rekening', 20)->nullable();
        $table->string('id_ref_kode', 30)->nullable();
        $table->boolean('has_ppn')->nullable();
        $table->boolean('has_pph_22')->nullable();
        $table->text('alasan_pecah_nota')->nullable();
        $table->boolean('is_beli_di_siplah')->default(false);
        $table->longText('details')->nullable();
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
      });
    }

    if (!Schema::hasTable('bos_rapbs')) {
      Schema::create('bos_rapbs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('id_anggaran_arkas', 30);
        $table->string('id_ref_kode', 30);
        $table->unsignedInteger('id_ref_tahun_anggaran')->nullable();
        $table->string('kode_rekening', 20);
        $table->string('id_barang', 40)->nullable();
        $table->string('urutan', 5)->nullable();
        $table->string('uraian', 500);
        $table->string('uraian_text', 255)->nullable();
        $table->decimal('volume', 12, 2);
        $table->string('satuan', 30)->nullable();
        $table->decimal('harga_satuan', 15, 2);
        $table->decimal('jumlah', 15, 2);
        $table->decimal('v1', 12, 2)->nullable();
        $table->string('s1', 30)->nullable();
        $table->decimal('v2', 12, 2)->nullable();
        $table->string('s2', 30)->nullable();
        $table->decimal('v3', 12, 2)->nullable();
        $table->string('s3', 30)->nullable();
        $table->decimal('v4', 12, 2)->nullable();
        $table->string('s4', 30)->nullable();
        $table->text('keterangan')->nullable();
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'id_anggaran_arkas']);
        $table->index(['sekolah_id', 'id_ref_kode']);
      });
    }

    if (!Schema::hasTable('bos_anggaran')) {
      Schema::create('bos_anggaran', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->unsignedInteger('id_ref_sumber_dana');
        $table->string('nama_sumber_dana', 150)->nullable();
        $table->unsignedSmallInteger('tahun_anggaran');
        $table->decimal('volume', 12, 2)->nullable();
        $table->decimal('harga_satuan', 15, 2)->nullable();
        $table->decimal('jumlah', 15, 2)->nullable();
        $table->decimal('sisa_anggaran', 15, 2)->nullable();
        $table->boolean('is_pengesahan')->default(false);
        $table->unsignedTinyInteger('is_approve')->default(0);
        $table->boolean('is_aktif')->default(false);
        $table->dateTime('tanggal_pengajuan')->nullable();
        $table->dateTime('tanggal_pengesahan')->nullable();
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'tahun_anggaran']);
      });
    }

    if (!Schema::hasTable('bos_ref_rekening')) {
      Schema::create('bos_ref_rekening', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        // id_arkas = "<kode_rekening>|<tahun>" (kunci ARKAS komposit)
        $table->string('id_arkas', 40);
        $table->string('kode_rekening', 20);
        $table->string('nama_rekening', 200);
        $table->unsignedSmallInteger('tahun')->nullable();
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'kode_rekening']);
      });
    }

    if (!Schema::hasTable('bos_ref_kode')) {
      Schema::create('bos_ref_kode', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('id_arkas', 30);
        $table->string('id_kode', 12)->nullable();
        $table->string('uraian_kode', 200);
        $table->unsignedTinyInteger('id_level_kode')->nullable();
        $table->unsignedSmallInteger('tahun')->nullable();
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
      });
    }

    if (!Schema::hasTable('bos_ref_bku')) {
      Schema::create('bos_ref_bku', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        // id_arkas = id_ref_bku ARKAS sebagai string
        $table->string('id_arkas', 30);
        $table->unsignedInteger('id_ref_bku');
        $table->string('nama_bku', 30);
        // ARKAS menyimpan kode_bku '' untuk sebagian baris;
        // string kosong tiba sebagai null, jadi boleh null.
        $table->string('kode_bku', 5)->nullable();
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'id_ref_bku']);
      });
    }

    if (!Schema::hasTable('bos_penjab')) {
      Schema::create('bos_penjab', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        // id_arkas = "<id_penjab>|<tahun>" (kunci ARKAS komposit)
        $table->string('id_arkas', 40);
        $table->string('id_penjab_arkas', 30);
        $table->unsignedSmallInteger('tahun');
        $table->date('tanggal_mulai');
        $table->date('tanggal_selesai');
        $table->string('ks', 50)->nullable();
        $table->string('nip_ks', 20)->nullable();
        $table->string('bendahara', 50)->nullable();
        $table->string('nip_bendahara', 20)->nullable();
        $table->string('komite', 50)->nullable();
        $table->boolean('soft_delete')->default(false);
        $table->dateTime('arkas_last_update')->nullable();
        $table->timestamps();
        $table->unique(['sekolah_id', 'id_arkas']);
        $table->index(['sekolah_id', 'tahun']);
      });
    }

    if (!Schema::hasTable('bos_sinkron_log')) {
      Schema::create('bos_sinkron_log', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->constrained('tabel_sekolah')->cascadeOnDelete();
        $table->string('tabel', 40);
        $table->unsignedInteger('diterima')->default(0);
        $table->unsignedInteger('tersimpan')->default(0);
        $table->string('agen_versi', 20)->nullable();
        $table->string('catatan', 255)->nullable();
        $table->timestamps();
        $table->index(['sekolah_id', 'created_at']);
      });
    }

    if (!Schema::hasTable('bos_sinkron_token')) {
      Schema::create('bos_sinkron_token', function (Blueprint $table) {
        $table->id();
        $table->foreignId('sekolah_id')->unique()->constrained('tabel_sekolah')->cascadeOnDelete();
        // Hanya hash sha256 token yang disimpan; token asli
        // ditampilkan sekali oleh perintah `bos:token`.
        $table->string('token_hash', 64)->unique();
        $table->dateTime('terakhir_dipakai')->nullable();
        $table->timestamps();
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('bos_sinkron_token');
    Schema::dropIfExists('bos_sinkron_log');
    Schema::dropIfExists('bos_penjab');
    Schema::dropIfExists('bos_ref_bku');
    Schema::dropIfExists('bos_ref_kode');
    Schema::dropIfExists('bos_ref_rekening');
    Schema::dropIfExists('bos_anggaran');
    Schema::dropIfExists('bos_rapbs');
    Schema::dropIfExists('bos_kas_nota');
    Schema::dropIfExists('bos_kas_umum');
  }
};
