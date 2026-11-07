<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Sekolah;
use Illuminate\Support\Facades\Auth;

class WaliKelasController extends Controller
{
  /**
   * Pastikan guru hanya bisa akses rombel miliknya sendiri
   */
  private function getRombelMilikGuru(int $rombel_id): Rombel
  {
    $rombel = Rombel::with('semester')->findOrFail($rombel_id);

    // Proteksi: pastikan rombel ini memang milik guru yang login
    if ($rombel->ptk_id !== Auth::user()->ptk->id) {
      abort(403, 'Anda bukan wali kelas dari rombel ini.');
    }

    return $rombel;
  }

  // =========================================================================
  // DATA SISWA DI KELAS INI
  // =========================================================================
  public function dataSiswa(int $rombel_id)
  {
    $rombel = $this->getRombelMilikGuru($rombel_id);

    // View daftar siswa dipakai bersama SiswaController::index dan
    // membutuhkan ketiga variabel ini; tanpa mereka halamannya
    // meledak (Undefined variable $semuaSekolah). Untuk Guru
    // isinya sama seperti yang diterima peran non-Admin/Operator
    // di index(): daftar sekolah lengkap, sisanya kosong.
    $sekolahs     = [];
    $rombels      = [];
    $semuaSekolah = Sekolah::with('detailTerbaru')->get();

    return view('content.siswa.index', compact('rombel', 'sekolahs', 'rombels', 'semuaSekolah'));
  }

  // =========================================================================
  // ABSENSI — diteruskan ke modul Presensi (halaman khusus
  // wali-kelas tidak pernah dibuat; view-nya tidak ada)
  // =========================================================================
  public function absensi(int $rombel_id)
  {
    $rombel = $this->getRombelMilikGuru($rombel_id);

    return redirect()->route('presensi.index', ['rombel_id' => $rombel->id]);
  }
}
