<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Gelar depan & gelar belakang PTK.
   *
   * Selama ini gelar tercampur di dalam nama_ptk sehingga nama
   * tampil kepanjangan di daftar-daftar sempit. Kini gelar
   * disimpan terpisah:
   * - gelar_depan: mis. "Dr.", "Drs.", "H."
   * - gelar_belakang: mis. "S.Pd.SD", "M.Pd"
   *
   * Nama resmi lengkap dirangkai saat tampil lewat accessor
   * Ptk::nama_lengkap ("GelarDepan Nama, GelarBelakang").
   * Data lama TIDAK diubah otomatis — gelar yang masih menyatu
   * di nama_ptk dipindah manual lewat form edit PTK.
   *
   * Dijaga hasColumn agar aman diulang.
   */
  public function up(): void
  {
    if (!Schema::hasTable('tabel_ptk')) {
      return;
    }

    if (!Schema::hasColumn('tabel_ptk', 'gelar_depan')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->string('gelar_depan', 50)->nullable()->after('nama_ptk');
      });
    }

    if (!Schema::hasColumn('tabel_ptk', 'gelar_belakang')) {
      Schema::table('tabel_ptk', function (Blueprint $table) {
        $table->string('gelar_belakang', 100)->nullable()->after('gelar_depan');
      });
    }
  }

  public function down(): void
  {
    if (!Schema::hasTable('tabel_ptk')) {
      return;
    }

    foreach (['gelar_belakang', 'gelar_depan'] as $kolom) {
      if (Schema::hasColumn('tabel_ptk', $kolom)) {
        Schema::table('tabel_ptk', function (Blueprint $table) use ($kolom) {
          $table->dropColumn($kolom);
        });
      }
    }
  }
};
