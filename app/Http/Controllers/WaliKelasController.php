<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
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

    return view('content.siswa.index', compact('rombel'));
  }

  // =========================================================================
  // ABSENSI (placeholder, dikembangkan sesuai kebutuhan)
  // =========================================================================
  public function absensi(int $rombel_id)
  {
    $rombel = $this->getRombelMilikGuru($rombel_id);

    return view('content.wali-kelas.absensi', compact('rombel'));
  }
}
