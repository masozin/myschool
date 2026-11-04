<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SekolahDetail;
use App\Models\Rombel;
use App\Models\Semester;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DataPeriodikController extends Controller
{
  /**
   * Menampilkan Form Salin Data
   */
  public function showCopyForm()
  {
    $sekolahId        = Auth::user()->ptk->sekolah_id;
    $semesterSekarang = Semester::smtId();

    // Cek: apakah ada rombel di semester LAIN (bukan semester aktif)?
    $bisaSalin = Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', '!=', $semesterSekarang)
      ->exists();

    // Detail sekolah dari semester sebelumnya (bukan semester aktif)
    $detailTerbaru = SekolahDetail::where('sekolah_id', $sekolahId)
      ->where('semester_id', '!=', $semesterSekarang)
      ->orderBy('semester_id', 'desc')
      ->first();

    // Rombel dari semester yang sama dengan detail di atas
    $rombelTerakhir = $detailTerbaru
      ? Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', $detailTerbaru->semester_id)
      ->get()
      : collect();

    return view('operator.copy_data_periodik', compact('detailTerbaru', 'rombelTerakhir', 'bisaSalin'));
  }

  /**
   * Eksekusi Salin Data dari Semester Sebelumnya
   */
  public function copyData(Request $request)
  {
    $request->validate([
      'semester_asal' => 'required|exists:tabel_semester,id',
    ]);

    $sekolahId        = Auth::user()->ptk->sekolah_id;
    $semesterSekarang = Semester::sessionSemesterId();
    $semesterLalu     = (int) $request->semester_asal;

    if ($semesterLalu === $semesterSekarang) {
      return back()->with('error', 'Semester asal tidak boleh sama dengan semester aktif.');
    }

    $status = SekolahDetail::status($sekolahId, $semesterSekarang);

    // Hanya tolak kalau KEDUANYA sudah ada
    if ($status['hasDetail'] && $status['hasRombel']) {
      return back()->with('error', 'Data untuk semester ini sudah tersedia.');
    }

    // Validasi SEBELUM transaksi dimulai
    $oldDetail = SekolahDetail::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterLalu)
      ->first();

    $oldRombels = Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterLalu)
      ->get();

    if (!$status['hasDetail'] && !$oldDetail) {
      return back()->with('error', 'Data sekolah pada semester asal tidak ditemukan.');
    }

    if (!$status['hasRombel'] && $oldRombels->isEmpty()) {
      return back()->with('error', 'Data rombel pada semester asal tidak ditemukan.');
    }

    DB::beginTransaction();
    try {
      // Salin detail hanya kalau belum ada
      if (!$status['hasDetail']) {
        $data = $oldDetail->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);
        $data['sekolah_id']  = $sekolahId;
        $data['semester_id'] = $semesterSekarang;
        SekolahDetail::create($data);
      }

      // Salin rombel hanya kalau belum ada
      if (!$status['hasRombel']) {
        foreach ($oldRombels as $rombel) {
          $data = $rombel->toArray();
          unset($data['id'], $data['created_at'], $data['updated_at']);
          $data['sekolah_id']  = $sekolahId;
          $data['semester_id'] = $semesterSekarang;
          Rombel::create($data);
        }
      }

      DB::commit();
      return redirect()->route('dashboard')->with('success', 'Data berhasil disalin ke semester baru.');
    } catch (\Exception $e) {
      DB::rollBack();
      return back()->with('error', 'Gagal menyalin data: ' . $e->getMessage());
    }
  }
}
