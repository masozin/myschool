<?php

namespace App\Http\Controllers;

use App\Models\SklKomponen;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * Diakses hanya oleh Admin.
 * Kelola komponen nilai SKL per jenjang untuk SEMESTER KERJA
 * KELULUSAN (Semester::smtIdKelulusan) — semester yang sama dengan
 * yang dipakai pembuatan SKL memeriksa komponen. Sebelumnya halaman
 * ini memakai semester sesi biasa, sehingga komponen yang diatur
 * saat login di semester Ganjil tersimpan di semester yang salah
 * dan pembuatan SKL tetap melaporkannya "belum dikonfigurasi".
 */
class SklKomponenController extends Controller
{
  /**
   * Semester kerja kelulusan: sesi Genap → semester sesi; sesi
   * Ganjil → Genap tahun pelajaran sebelumnya.
   */
  private function getSemester(): Semester
  {
    return Semester::findOrFail((int) Semester::smtIdKelulusan());
  }

  // ── Index ─────────────────────────────────────────────────────────────

  public function index(Request $request)
  {
    $semester = $this->getSemester();
    $jenjang  = $request->get('jenjang', 'SD');

    $komponens  = SklKomponen::untuk($semester->id, $jenjang)->with('komponenSub')->get();
    $totalBobot = SklKomponen::totalBobot($semester->id, $jenjang);

    // Pilihan semester sumber untuk tombol Salin: semester lain
    // yang sudah memiliki komponen pada jenjang yang sedang dibuka
    $idSumber = SklKomponen::where('jenjang', $jenjang)
      ->where('semester_id', '!=', $semester->id)
      ->distinct()
      ->pluck('semester_id');

    $semesterSumber = Semester::whereIn('id', $idSumber)
      ->orderByDesc('kode_semester')
      ->get();

    $jumlahPerSumber = SklKomponen::where('jenjang', $jenjang)
      ->whereIn('semester_id', $idSumber)
      ->selectRaw('semester_id, COUNT(*) as jumlah')
      ->groupBy('semester_id')
      ->pluck('jumlah', 'semester_id');

    return view('content.skl.komponen.index', compact(
      'komponens',
      'jenjang',
      'semester',
      'totalBobot',
      'semesterSumber',
      'jumlahPerSumber'
    ));
  }

  // ── Salin dari Semester Lain ────────────────────────────────────────

  /**
   * Salin seluruh komponen + sub-komponen satu jenjang dari
   * semester sumber ke semester kerja kelulusan. Tujuan harus
   * masih kosong untuk jenjang itu agar tidak terjadi duplikasi
   * dan total bobot sumber (maksimal 100) terbawa apa adanya.
   */
  public function salin(Request $request)
  {
    $target = $this->getSemester();

    $data = $request->validate([
      'semester_sumber' => 'required|integer|exists:tabel_semester,id',
      'jenjang'          => 'required|in:SD,SMP,SMA,SMK',
    ]);

    if ((int) $data['semester_sumber'] === (int) $target->id) {
      return back()->with('error', 'Semester sumber sama dengan semester tujuan.');
    }

    $sumber = SklKomponen::untuk((int) $data['semester_sumber'], $data['jenjang'])
      ->with(['komponenSub' => fn($q) => $q->orderBy('urutan')])
      ->get();

    if ($sumber->isEmpty()) {
      return back()->with('error', 'Semester sumber tidak memiliki komponen untuk jenjang ' . $data['jenjang'] . '.');
    }

    $sudahAda = SklKomponen::untuk($target->id, $data['jenjang'])->count();
    if ($sudahAda > 0) {
      return back()->with('error', 'Semester ini sudah memiliki ' . $sudahAda . ' komponen untuk jenjang ' . $data['jenjang'] . '. Hapus terlebih dahulu bila ingin menyalin ulang.');
    }

    $jumlahSub = 0;
    DB::transaction(function () use ($sumber, $target, $data, &$jumlahSub) {
      foreach ($sumber as $komp) {
        $baru = SklKomponen::create([
          'semester_id' => $target->id,
          'jenjang'     => $data['jenjang'],
          'kode'        => $komp->kode,
          'label'       => $komp->label,
          'bobot'       => $komp->bobot,
          'keterangan'  => $komp->keterangan,
          'urutan'      => $komp->urutan,
        ]);

        foreach ($komp->komponenSub as $sub) {
          $baru->komponenSub()->create([
            'label'      => $sub->label,
            'keterangan' => $sub->keterangan,
            'urutan'     => $sub->urutan,
          ]);
          $jumlahSub++;
        }
      }
    });

    return redirect()
      ->route('skl.komponen.index', ['jenjang' => $data['jenjang']])
      ->with('success', 'Berhasil menyalin ' . $sumber->count() . ' komponen dan ' . $jumlahSub . ' sub-komponen ke semester ini.');
  }

  // ── Create ────────────────────────────────────────────────────────────

  public function create(Request $request)
  {
    $semester   = $this->getSemester();
    $jenjang    = $request->get('jenjang', 'SD');
    $totalBobot = SklKomponen::totalBobot($semester->id, $jenjang);

    return view('content.skl.komponen.form', compact('semester', 'jenjang', 'totalBobot'));
  }

  // ── Store ─────────────────────────────────────────────────────────────

  public function store(Request $request)
  {
    $semester = $this->getSemester();

    $data = $request->validate([
      'jenjang'    => 'required|in:SD,SMP,SMA,SMK',
      'kode'       => 'required|string|max:50',
      'label'      => 'required|string|max:100',
      'bobot'      => 'required|integer|min:1|max:100',
      'keterangan' => 'nullable|string|max:255',
      'urutan'     => 'required|integer|min:0',
    ]);

    // semester_id dari session, bukan dari form
    $data['semester_id'] = $semester->id;

    $totalAda = SklKomponen::totalBobot($semester->id, $data['jenjang']);
    if ($totalAda + $data['bobot'] > 100) {
      return back()->withInput()->withErrors([
        'bobot' => 'Total bobot akan menjadi ' . ($totalAda + $data['bobot']) . '%. Maksimal 100%.',
      ]);
    }

    SklKomponen::create($data);

    return redirect()
      ->route('skl.komponen.index', ['jenjang' => $data['jenjang']])
      ->with('success', 'Komponen berhasil ditambahkan.');
  }

  // ── Edit ─────────────────────────────────────────────────────────────

  public function edit(SklKomponen $komponen)
  {
    $semester = $this->getSemester();

    // Pastikan komponen milik semester aktif
    abort_if($komponen->semester_id !== $semester->id, 403, 'Komponen ini bukan milik semester aktif.');

    // Total bobot SELAIN komponen ini (untuk progress bar sisa)
    $totalBobot = SklKomponen::where('semester_id', $semester->id)
      ->where('jenjang', $komponen->jenjang)
      ->where('id', '!=', $komponen->id)
      ->sum('bobot');

    return view('content.skl.komponen.form', compact('komponen', 'semester', 'totalBobot'));
  }

  // ── Update ────────────────────────────────────────────────────────────

  public function update(Request $request, SklKomponen $komponen)
  {
    $semester = $this->getSemester();
    abort_if($komponen->semester_id !== $semester->id, 403, 'Komponen ini bukan milik semester aktif.');

    $data = $request->validate([
      'label'      => 'required|string|max:100',
      'bobot'      => 'required|integer|min:1|max:100',
      'keterangan' => 'nullable|string|max:255',
      'urutan'     => 'required|integer|min:0',
    ]);

    $totalTanpaIni = SklKomponen::where('semester_id', $semester->id)
      ->where('jenjang', $komponen->jenjang)
      ->where('id', '!=', $komponen->id)
      ->sum('bobot');

    if ($totalTanpaIni + $data['bobot'] > 100) {
      return back()->withInput()->withErrors([
        'bobot' => 'Total bobot akan menjadi ' . ($totalTanpaIni + $data['bobot']) . '%. Maksimal 100%.',
      ]);
    }

    $komponen->update($data);

    return redirect()
      ->route('skl.komponen.index', ['jenjang' => $komponen->jenjang])
      ->with('success', 'Komponen berhasil diperbarui.');
  }

  // ── Destroy ───────────────────────────────────────────────────────────

  public function destroy(SklKomponen $komponen)
  {
    $komponen->delete();

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Komponen berhasil dihapus.']);
    }

    return redirect()
      ->route('skl.komponen.index', ['jenjang' => $komponen->jenjang])
      ->with('success', 'Komponen berhasil dihapus.');
  }
}
