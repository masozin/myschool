<?php

namespace App\Http\Controllers;

use App\Models\SklKonfigurasi;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SklKonfigurasiController extends Controller
{
  private function getContext(): array
  {
    $sekolahId = Auth::user()->ptk?->sekolah_id;
    abort_if(!$sekolahId, 403, "Akun Anda belum terhubung ke sekolah.");
    $semesterId = (int) session('semester_id');
    $semester   = Semester::findOrFail($semesterId);

    return [$sekolahId, $semesterId, $semester];
  }

  public function edit()
  {
    [$sekolahId, $semesterId, $semester] = $this->getContext();

    // $konfigurasi = SklKonfigurasi::firstOrDefault($sekolahId, $semesterId);
    $konfigurasi = SklKonfigurasi::untukSekolah($sekolahId, (int) $semesterId);

    return view('content.skl.konfigurasi', compact('konfigurasi', 'semester'));
  }

  public function update(Request $request)
  {
    [$sekolahId, $semesterId] = $this->getContext();

    $request->validate([
      'mode_input'      => 'required|in:jadi,manual',
      'tempat_terbit'   => 'nullable|string|max:100',
      'tanggal_terbit'  => 'nullable|date',
      'prefix_nomor'    => 'nullable|string|max:20',
    ]);

    $konfigurasi = SklKonfigurasi::firstOrDefault($sekolahId, $semesterId);

    // Mode input terkunci setelah ada SKL pertama dibuat,
    // tapi tempat/tanggal/prefix tetap bisa diubah kapan saja
    if ($konfigurasi->is_terkunci && $request->filled('mode_input')) {
      if ($request->expectsJson()) {
        return response()->json([
          'message' => 'Mode input sudah terkunci karena sudah ada data SKL yang dibuat.',
        ], 422);
      }
      return back()->withErrors(['mode_input' => 'Mode input sudah terkunci.']);
    }

    $updateData = $request->only(['tempat_terbit', 'tanggal_terbit', 'prefix_nomor']);

    if (!$konfigurasi->is_terkunci && $request->filled('mode_input')) {
      $updateData['mode_input'] = $request->mode_input;
    }

    $konfigurasi->update($updateData);

    if ($request->expectsJson()) {
      return response()->json([
        'message'     => 'Konfigurasi berhasil disimpan.',
        'konfigurasi' => $konfigurasi->fresh(),
      ]);
    }

    return back()->with('success', 'Konfigurasi berhasil disimpan.');
  }
}
