<?php

namespace App\Http\Controllers;

use App\Models\IjazahKomponen;
use App\Models\IjazahSubKomponen;
use Illuminate\Http\Request;

/**
 * Kelola sub-komponen dari sebuah komponen nilai ijazah.
 * Diakses hanya oleh Admin.
 */
class IjazahSubKomponenController extends Controller
{
  /**
   * Tampilkan form kelola sub-komponen untuk satu komponen.
   * Halaman ini sekaligus bisa tambah, urut-ulang, dan hapus sub-komponen.
   */
  public function index(IjazahKomponen $komponen)
  {
    $subKomponen = $komponen->subKomponen()->orderBy('urutan')->get();

    return view('content.ijazah.komponen.sub', compact('komponen', 'subKomponen'));
  }

  public function store(Request $request, IjazahKomponen $komponen)
  {
    $data = $request->validate([
      'label'  => 'required|string|max:100',
      'urutan' => 'required|integer|min:0',
    ]);

    $sub = $komponen->subKomponen()->create($data);

    if ($request->expectsJson()) {
      return response()->json([
        'message'      => 'Sub-komponen berhasil ditambahkan.',
        'sub_komponen' => $sub,
      ]);
    }

    return redirect()
      ->route('ijazah.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil ditambahkan.');
  }

  public function update(Request $request, IjazahKomponen $komponen, IjazahSubKomponen $sub)
  {
    $data = $request->validate([
      'label'  => 'required|string|max:100',
      'urutan' => 'required|integer|min:0',
    ]);

    $sub->update($data);

    if ($request->expectsJson()) {
      return response()->json(['message' => 'Sub-komponen berhasil diperbarui.']);
    }

    return redirect()
      ->route('ijazah.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil diperbarui.');
  }

  public function destroy(IjazahKomponen $komponen, IjazahSubKomponen $sub)
  {
    $sub->delete();

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Sub-komponen berhasil dihapus.']);
    }

    return redirect()
      ->route('ijazah.komponen.sub.index', $komponen)
      ->with('success', 'Sub-komponen berhasil dihapus.');
  }
}
