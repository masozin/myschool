<?php

namespace App\Http\Controllers;

use App\Models\SklKomponen;
use App\Models\SklKomponenSub;
use Illuminate\Http\Request;

/**
 * Kelola sub-komponen dari sebuah komponen nilai SKL.
 * Diakses hanya oleh Admin.
 */
class SklKomponenSubController extends Controller
{
  public function index(SklKomponen $komponen)
  {
    $komponenSub = $komponen->komponenSub()->orderBy('urutan')->get();

    return view('content.skl.komponen.sub', compact('komponen', 'komponenSub'));
  }

  public function store(Request $request, SklKomponen $komponen)
  {
    $data = $request->validate([
      'label'      => 'required|string|max:100',
      'keterangan' => 'nullable|string|max:100',
      'urutan'     => 'required|integer|min:0',
    ]);

    $sub = $komponen->komponenSub()->create($data);

    if ($request->expectsJson()) {
      return response()->json([
        'message'      => 'Sub-komponen berhasil ditambahkan.',
        'komponen_sub' => $sub,
      ]);
    }

    return redirect()
      ->route('skl.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil ditambahkan.');
  }

  public function update(Request $request, SklKomponen $komponen, SklKomponenSub $sub)
  {
    $data = $request->validate([
      'label'      => 'required|string|max:100',
      'keterangan' => 'nullable|string|max:100',
      'urutan'     => 'required|integer|min:0',
    ]);

    $sub->update($data);

    if ($request->expectsJson()) {
      return response()->json(['message' => 'Sub-komponen berhasil diperbarui.']);
    }

    return redirect()
      ->route('skl.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil diperbarui.');
  }

  public function destroy(SklKomponen $komponen, SklKomponenSub $sub)
  {
    $sub->delete();

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Sub-komponen berhasil dihapus.']);
    }

    return redirect()
      ->route('skl.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil dihapus.');
  }
}
