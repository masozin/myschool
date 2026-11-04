<?php

namespace App\Http\Controllers;

use App\Models\Kurikulum;
use App\Models\Ptk;
use App\Models\Rombel;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use App\Models\RombelAnggota;

class RombelController extends Controller
{
  public function index()
  {
    $ptks    = Ptk::where('sekolah_id', Auth::user()->ptk->sekolah_id)
      ->where('jabatan', 'Guru')
      ->get();
    $sekolah = Auth::user()->ptk->sekolah;
    $kurikulum = Kurikulum::all();

    return view('content.rombel.index', compact('ptks', 'sekolah', 'kurikulum'));
  }

  public function tabel()
  {
    $semesterId = session('semester_id');
    $sekolahId  = Auth::user()->ptk->sekolah_id;

    $rombel = Rombel::with(['ptk', 'semester', 'kurikulum'])
      ->where('semester_id', $semesterId)
      ->where('sekolah_id', $sekolahId)
      ->withCount(['rombelAnggota as jumlah_siswa' => fn($q) => $q->whereNull('end_date')])
      ->withCount(['rombelAnggota as jumlah_l' => fn($q) => $q->whereNull('end_date')
        ->whereHas('siswa', fn($q2) => $q2->where('jenis_kelamin', 'L'))])
      ->withCount(['rombelAnggota as jumlah_p' => fn($q) => $q->whereNull('end_date')
        ->whereHas('siswa', fn($q2) => $q2->where('jenis_kelamin', 'P'))])
      ->get();

    // Breakdown gender per tingkat (untuk chart)
    $anggotaAktif = RombelAnggota::with(['siswa:id,jenis_kelamin', 'rombel:id,tingkat,sekolah_id,semester_id'])
      ->whereNull('end_date')
      ->whereHas('rombel', fn($q) => $q->where('sekolah_id', $sekolahId)->where('semester_id', $semesterId))
      ->get();

    $statistikTingkat = [];
    foreach ($anggotaAktif as $a) {
      if (!$a->rombel) continue;
      $tingkat = $a->rombel->tingkat;
      $jk = $a->siswa->jenis_kelamin ?? null;

      if (!isset($statistikTingkat[$tingkat])) {
        $statistikTingkat[$tingkat] = ['tingkat' => $tingkat, 'L' => 0, 'P' => 0];
      }
      if ($jk === 'L') $statistikTingkat[$tingkat]['L']++;
      elseif ($jk === 'P') $statistikTingkat[$tingkat]['P']++;
    }

    return response()->json([
      'data' => $rombel->map(fn($r) => [
        'id'           => $r->id,
        'nama_rombel'  => $r->nama_rombel,
        'tingkat'      => $r->tingkat,
        'kurikulum'    => $r->kurikulum->nama ?? '-',
        'kurikulum_id' => $r->kurikulum_id,
        'wali_kelas'   => $r->ptk->nama_ptk ?? '-',
        'semester'     => $r->semester->nama_semester ?? '-',
        'ptk_id'       => $r->ptk_id,
        'jumlah_l'     => $r->jumlah_l,
        'jumlah_p'     => $r->jumlah_p,
        'jumlah_siswa' => $r->jumlah_siswa,
        'is_locked'    => $r->jumlah_siswa > 0,
        'can_edit'     => Auth::user()->can('update', $r),
        'can_delete'   => Auth::user()->can('delete', $r),
      ]),
      'statistik_tingkat' => array_values($statistikTingkat),
    ]);
  }

  public function store(Request $request)
  {
    Gate::authorize('create', Rombel::class);

    $sekolahId  = Auth::user()->ptk->sekolah_id;
    $semesterId = session('semester_id');

    $validated = $request->validate([
      'nama_rombel' => 'required|string|max:50',
      'tingkat'     => 'required|integer',
      'ptk_id'       => [
        'required',
        Rule::exists('tabel_ptk', 'id')->where('sekolah_id', $sekolahId),
      ],
      'kurikulum_id' => 'required|exists:tabel_kurikulum,id',
    ]);

    $sudahAda = Rombel::where('nama_rombel', $validated['nama_rombel'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->exists();

    if ($sudahAda) {
      return response()->json([
        'message' => 'Rombel dengan nama ini sudah ada di semester yang sama.',
        'errors'  => ['nama_rombel' => ['Nama rombel sudah digunakan di semester ini.']]
      ], 422);
    }

    Rombel::create([
      ...$validated,
      'sekolah_id'  => $sekolahId,
      'semester_id' => $semesterId,
    ]);

    return response()->json(['message' => 'Rombel berhasil dibuat']);
  }

  public function update(Request $request, $id)
  {
    $rombel = Rombel::findOrFail($id);
    $sekolahId  = Auth::user()->ptk->sekolah_id;
    Gate::authorize('update', $rombel);

    $sudahAdaAnggota = $rombel->rombelAnggota()->whereNull('end_date')->exists();

    $rules = [
      'nama_rombel' => 'required|string|max:50',
      'ptk_id'       => [
        'required',
        Rule::exists('tabel_ptk', 'id')->where('sekolah_id', $sekolahId),
      ],
      'kurikulum_id' => 'required|exists:tabel_kurikulum,id',
    ];

    if (!$sudahAdaAnggota) {
      $rules['tingkat'] = 'required|integer';
    }

    $validated = $request->validate($rules);

    $rombel->update([
      ...$validated,
      'tingkat' => $sudahAdaAnggota ? $rombel->tingkat : $validated['tingkat'],
    ]);

    return response()->json([
      'message'   => 'Berhasil update data kelas',
      'is_locked' => $sudahAdaAnggota,
    ]);
  }

  public function destroy($id)
  {
    try {
      $rombel      = Rombel::findOrFail($id);
      Gate::authorize('delete', $rombel);
      $jumlahSiswa = $rombel->rombelAnggota()->whereNull('end_date')->count();

      if ($jumlahSiswa) {
        return response()->json([
          'jumlah_siswa' => $jumlahSiswa,
          'nama_rombel'  => $rombel->nama_rombel,
        ], 422);
      }

      $rombel->delete();

      return response()->json(['message' => 'Rombel berhasil dihapus']);
    } catch (\Exception $e) {
      return response()->json([
        'message' => 'Terjadi kesalahan saat menghapus data.',
        'error'   => $e->getMessage()
      ], 500);
    }
  }
}
