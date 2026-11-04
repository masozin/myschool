<?php

namespace App\Http\Controllers;

use App\Models\IjazahKomponen;
use Illuminate\Http\Request;

/**
 * Diakses hanya oleh Admin.
 * Kelola konfigurasi komponen nilai ijazah per jenjang per tahun pelajaran.
 */
class IjazahKomponenController extends Controller
{
  public function index(Request $request)
  {
    $jenjang        = $request->get('jenjang', 'SD');
    $tahunPelajaran = $request->get('tahun_pelajaran', '2025/2026');

    $komponens    = IjazahKomponen::untuk($jenjang, $tahunPelajaran)->with('subKomponen')->get();
    $totalBobot   = IjazahKomponen::totalBobot($jenjang, $tahunPelajaran);

    return view('content.ijazah.komponen.index', compact(
      'komponens',
      'jenjang',
      'tahunPelajaran',
      'totalBobot'
    ));
  }

  public function create(Request $request)
  {
    // Kirim sisa bobot agar preview formula di view bisa dihitung
    $jenjang        = $request->get('jenjang', 'SD');
    $tahunPelajaran = $request->get('tahun_pelajaran', '2025/2026');
    $totalBobot     = IjazahKomponen::totalBobot($jenjang, $tahunPelajaran);

    return view('content.ijazah.komponen.form', compact('totalBobot'));
  }

  public function store(Request $request)
  {
    $data = $request->validate([
      'jenjang'         => 'required|in:SD,SMP,SMA,SMK',
      'tahun_pelajaran' => 'required|string|max:9',
      'kode'            => 'required|string|max:50',
      'label'           => 'required|string|max:100',
      'bobot'           => 'required|integer|min:1|max:100',
      'keterangan'      => 'nullable|string|max:255',
      'urutan'          => 'required|integer|min:0',
    ]);

    // Cek total bobot tidak melebihi 100
    $totalAda = IjazahKomponen::totalBobot($data['jenjang'], $data['tahun_pelajaran']);
    if ($totalAda + $data['bobot'] > 100) {
      return back()->withInput()->withErrors([
        'bobot' => "Total bobot akan menjadi " . ($totalAda + $data['bobot']) . "%. Maksimal 100%.",
      ]);
    }

    IjazahKomponen::create($data);

    return redirect()
      ->route('ijazah.komponen.index', [
        'jenjang'         => $data['jenjang'],
        'tahun_pelajaran' => $data['tahun_pelajaran'],
      ])
      ->with('success', 'Komponen berhasil ditambahkan.');
  }

  public function edit(IjazahKomponen $komponen)
  {
    // totalBobot = bobot semua komponen SELAIN yang sedang diedit
    // Dipakai view untuk progress bar "sisa bobot"
    $totalBobot = IjazahKomponen::where('jenjang', $komponen->jenjang)
      ->where('tahun_pelajaran', $komponen->tahun_pelajaran)
      ->where('id', '!=', $komponen->id)
      ->sum('bobot');

    return view('content.ijazah.komponen.form', compact('komponen', 'totalBobot'));
  }

  public function update(Request $request, IjazahKomponen $komponen)
  {
    $data = $request->validate([
      'label'      => 'required|string|max:100',
      'bobot'      => 'required|integer|min:1|max:100',
      'keterangan' => 'nullable|string|max:255',
      'urutan'     => 'required|integer|min:0',
    ]);

    // Hitung total bobot TANPA komponen ini, lalu tambah bobot baru
    $totalTanpaIni = IjazahKomponen::where('jenjang', $komponen->jenjang)
      ->where('tahun_pelajaran', $komponen->tahun_pelajaran)
      ->where('id', '!=', $komponen->id)
      ->sum('bobot');

    if ($totalTanpaIni + $data['bobot'] > 100) {
      return back()->withInput()->withErrors([
        'bobot' => "Total bobot akan menjadi " . ($totalTanpaIni + $data['bobot']) . "%. Maksimal 100%.",
      ]);
    }

    $komponen->update($data);

    return redirect()
      ->route('ijazah.komponen.index', [
        'jenjang'         => $komponen->jenjang,
        'tahun_pelajaran' => $komponen->tahun_pelajaran,
      ])
      ->with('success', 'Komponen berhasil diperbarui.');
  }

  public function destroy(IjazahKomponen $komponen)
  {
    $komponen->delete();

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Komponen berhasil dihapus.']);
    }

    return redirect()
      ->route('ijazah.komponen.index', [
        'jenjang'         => $komponen->jenjang,
        'tahun_pelajaran' => $komponen->tahun_pelajaran,
      ])
      ->with('success', 'Komponen berhasil dihapus.');
  }
} 
