<?php

namespace App\Http\Controllers;

use App\Models\MapelJenjang;
use App\Models\MapelKelompok;
use App\Models\MapelMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MapelMasterController extends Controller
{
  private function isAdmin(): bool
  {
    return Auth::user()->hasRole('Admin');
  }

  // ─── INDEX ───────────────────────────────────────────────────────────────
  public function index()
  {
    if (!$this->isAdmin()) {
      abort(403, 'Anda tidak memiliki akses ke fitur ini.');
    }

    $mapelKelompok = MapelKelompok::orderBy('urutan')->get(['id', 'nama', 'jenjang_scope']);
    return view('content.mapel-master.index', compact('mapelKelompok'));
  }

  // ─── TABLE (JSON) ─────────────────────────────────────────────────────────
  public function table()
  {
    if (!$this->isAdmin()) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $mapel = MapelMaster::with(['kelompok', 'mapelJenjang'])
      ->orderBy('mapel_kelompok_id')
      ->orderBy('nama')
      ->get()
      ->map(fn($item) => [
        'id'             => $item->id,
        'nama'           => $item->nama,
        'nama_singkat'   => $item->nama_singkat,
        'is_aktif'       => $item->is_aktif,
        'kelompok_id'    => $item->kelompok?->id,
        'kelompok_kode'  => $item->kelompok?->kode,
        'kelompok_nama'  => $item->kelompok?->nama,
        'jenjang'        => $item->mapelJenjang->sortBy('jenjang')->pluck('jenjang')->values(),
        'jenjang_detail' => $item->mapelJenjang
          ->sortBy('jenjang')
          ->map(fn($j) => [
            'jenjang'       => $j->jenjang,
            'kelas_mulai'   => $j->kelas_mulai,
            'kelas_selesai' => $j->kelas_selesai,
          ])
          ->values(),
      ]);

    return response()->json($mapel);
  }

  // ─── STORE ────────────────────────────────────────────────────────────────
  public function store(Request $request)
  {
    if (!$this->isAdmin()) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
      'mapel_kelompok_id'       => 'required|exists:tabel_mapel_kelompok,id',
      'nama'                    => 'required|string|max:100',
      'nama_singkat'            => 'nullable|string|max:20',
      'is_aktif'                => 'boolean',
      'jenjang'                 => 'required|array|min:1',
      'jenjang.*.jenjang'       => 'required|string|in:SD,SMP,SMA,SMK',
      'jenjang.*.kelas_mulai'   => 'required|integer|min:1|max:12',
      'jenjang.*.kelas_selesai' => 'required|integer|min:1|max:12|gte:jenjang.*.kelas_mulai',
    ]);

    $mapel = MapelMaster::create([
      'mapel_kelompok_id' => $request->mapel_kelompok_id,
      'nama'              => $request->nama,
      'nama_singkat'      => $request->nama_singkat,
      'is_aktif'          => $request->boolean('is_aktif', true),
    ]);

    foreach ($request->jenjang as $j) {
      $mapel->mapelJenjang()->create($j);
    }

    return response()->json([
      'message' => 'Mata pelajaran berhasil ditambahkan.',
      'data'    => $mapel->load('mapelJenjang'),
    ], 201);
  }

  // ─── UPDATE ───────────────────────────────────────────────────────────────
  public function update(Request $request, MapelMaster $mapelMaster)
  {
    if (!$this->isAdmin()) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
      'mapel_kelompok_id'       => 'required|exists:tabel_mapel_kelompok,id',
      'nama'                    => 'required|string|max:100',
      'nama_singkat'            => 'nullable|string|max:20',
      'is_aktif'                => 'boolean',
      'jenjang'                 => 'required|array|min:1',
      'jenjang.*.jenjang'       => 'required|string|in:SD,SMP,SMA,SMK',
      'jenjang.*.kelas_mulai'   => 'required|integer|min:1|max:12',
      'jenjang.*.kelas_selesai' => 'required|integer|min:1|max:12|gte:jenjang.*.kelas_mulai',
    ]);

    $mapelMaster->update([
      'mapel_kelompok_id' => $request->mapel_kelompok_id,
      'nama'              => $request->nama,
      'nama_singkat'      => $request->nama_singkat,
      'is_aktif'          => $request->boolean('is_aktif'),
    ]);

    $mapelMaster->mapelJenjang()->delete();
    foreach ($request->jenjang as $j) {
      $mapelMaster->mapelJenjang()->create($j);
    }

    return response()->json([
      'message' => 'Mata pelajaran berhasil diperbarui.',
      'data'    => $mapelMaster->load('mapelJenjang'),
    ]);
  }

  // ─── DESTROY ─────────────────────────────────────────────────────────────
  public function destroy(MapelMaster $mapelMaster)
  {
    if (!$this->isAdmin()) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $mapelMaster->mapelJenjang()->delete();
    $mapelMaster->delete();

    return response()->json(['message' => 'Mata pelajaran berhasil dihapus.']);
  }
}
