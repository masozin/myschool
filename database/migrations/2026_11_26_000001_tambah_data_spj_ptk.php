<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Data PTK untuk kebutuhan SPJ — daftar penerima honor/tanda terima
 * membutuhkan NPWP, alamat, dan nomor rekening + bank per orang, yang
 * selama ini tidak tersimpan di mana pun (tidak ada di BKU/ARKAS dan
 * tidak ada di PTK). Data itu kini disimpan sekali di data induk PTK
 * agar daftar penerima SPJ bisa diisi langsung dari daftar PTK.
 *
 * Pangkat/golongan disimpan sebagai RIWAYAT (tabel_ptk_riwayat_pangkat)
 * karena pangkat berubah dari waktu ke waktu: pangkat yang berlaku
 * adalah entri dengan TMT terbaru, dan riwayat lama tetap tersimpan
 * untuk dokumen periode sebelumnya.
 */
return new class extends Migration
{
  public function up(): void
  {
    Schema::table('tabel_ptk', function (Blueprint $table) {
      if (!Schema::hasColumn('tabel_ptk', 'npwp')) {
        $table->string('npwp', 30)->nullable()->after('nip');
      }
      if (!Schema::hasColumn('tabel_ptk', 'alamat')) {
        $table->text('alamat')->nullable();
      }
      if (!Schema::hasColumn('tabel_ptk', 'no_rekening')) {
        $table->string('no_rekening', 30)->nullable();
      }
      if (!Schema::hasColumn('tabel_ptk', 'nama_bank')) {
        $table->string('nama_bank', 100)->nullable();
      }
    });

    if (!Schema::hasTable('tabel_ptk_riwayat_pangkat')) {
      Schema::create('tabel_ptk_riwayat_pangkat', function (Blueprint $table) {
        $table->id();
        $table->foreignId('ptk_id')->constrained('tabel_ptk')->cascadeOnDelete();
        $table->string('pangkat', 100);
        $table->string('golongan', 10);
        $table->date('tmt');
        $table->string('nomor_sk', 100)->nullable();
        $table->date('tanggal_sk')->nullable();
        $table->timestamps();
      });
    }
  }

  public function down(): void
  {
    Schema::dropIfExists('tabel_ptk_riwayat_pangkat');

    Schema::table('tabel_ptk', function (Blueprint $table) {
      $kolom = array_filter(
        ['npwp', 'alamat', 'no_rekening', 'nama_bank'],
        fn($nama) => Schema::hasColumn('tabel_ptk', $nama)
      );

      if ($kolom) {
        $table->dropColumn(array_values($kolom));
      }
    });
  }
};
