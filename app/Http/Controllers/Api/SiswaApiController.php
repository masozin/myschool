<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\SiswaRegistrasi;
use Illuminate\Http\Request;

/**
 * API data siswa untuk aplikasi mobile guru: daftar siswa di
 * sekolah pengguna (per semester, bisa per rombel) dan detail
 * biodata satu siswa. Cakupan selalu dibatasi sekolah pengguna
 * dari data PTK-nya; Admin tanpa PTK wajib menyebut sekolah_id.
 * Nomor NIK (siswa maupun orang tua) sengaja TIDAK dikirim —
 * aplikasi mobile tidak memerlukannya.
 */
class SiswaApiController extends Controller
{
  private function sekolahIdUntuk(Request $request): ?int
  {
    $user = $request->user();

    if ($user->ptk?->sekolah_id) {
      return (int) $user->ptk->sekolah_id;
    }

    if ($user->hasRole('Admin') && $request->filled('sekolah_id')) {
      return (int) $request->sekolah_id;
    }

    return null;
  }

  private function semesterUntuk(Request $request): ?Semester
  {
    if ($request->filled('semester_id')) {
      return Semester::find($request->semester_id);
    }

    return Semester::where('is_active', true)->first();
  }

  /** Registrasi terakhir setiap siswa pada satu sekolah, dipetakan per siswa_id. */
  private function petaRegistrasi(int $sekolahId, $siswaIds): array
  {
    return SiswaRegistrasi::where('sekolah_id', $sekolahId)
      ->whereIn('siswa_id', $siswaIds)
      ->orderByDesc('id')
      ->get()
      ->unique('siswa_id')
      ->keyBy('siswa_id')
      ->all();
  }

  /** Daftar siswa sekolah pengguna pada satu semester. */
  public function daftar(Request $request)
  {
    $sekolahId = $this->sekolahIdUntuk($request);

    if (!$sekolahId) {
      return response()->json([
        'message' => 'Akun Anda tidak terhubung ke sekolah mana pun. Admin perlu menyertakan sekolah_id.',
      ], 403);
    }

    $semester = $this->semesterUntuk($request);

    if (!$semester) {
      return response()->json(['message' => 'Semester tidak ditemukan.'], 422);
    }

    $rombelQuery = Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semester->id);

    if ($request->filled('rombel_id')) {
      $rombelQuery->where('id', $request->rombel_id);
    }

    $rombelIds = $rombelQuery->pluck('id');

    $anggota = RombelAnggota::with(['siswa', 'rombel'])
      ->whereIn('rombel_id', $rombelIds)
      ->get();

    if ($request->filled('cari')) {
      $cari = mb_strtolower($request->cari);
      $anggota = $anggota->filter(fn($a) =>
        str_contains(mb_strtolower($a->siswa->nama_siswa), $cari)
        || str_contains((string) $a->siswa->nisn, $cari)
      )->values();
    }

    $petaReg = $this->petaRegistrasi($sekolahId, $anggota->pluck('siswa_id'));

    $data = $anggota
      ->sortBy(fn($a) => [$a->rombel->tingkat, $a->rombel->nama_rombel, $a->siswa->nama_siswa])
      ->map(fn($a) => [
        'siswa_id' => $a->siswa->id,
        'nama_siswa' => $a->siswa->nama_siswa,
        'nisn' => $a->siswa->nisn,
        'nis' => $petaReg[$a->siswa_id]->nis ?? null,
        'jenis_kelamin' => $a->siswa->jenis_kelamin,
        'tempat_lahir' => $a->siswa->tempat_lahir,
        'tgl_lahir' => $a->siswa->tgl_lahir,
        'photo' => $a->siswa->photo,
        'rombel' => [
          'id' => $a->rombel->id,
          'nama_rombel' => $a->rombel->nama_rombel,
          'tingkat' => $a->rombel->tingkat,
        ],
      ])
      ->values();

    return response()->json([
      'semester' => [
        'id' => $semester->id,
        'tahun_pelajaran' => $semester->tahun_pelajaran,
        'semester' => $semester->semester,
      ],
      'jumlah' => $data->count(),
      'siswa' => $data,
    ]);
  }

  /** Detail biodata satu siswa — hanya bila siswa itu terdaftar di sekolah pengguna. */
  public function detail(Request $request, int $id)
  {
    $sekolahId = $this->sekolahIdUntuk($request);

    if (!$sekolahId) {
      return response()->json([
        'message' => 'Akun Anda tidak terhubung ke sekolah mana pun. Admin perlu menyertakan sekolah_id.',
      ], 403);
    }

    $siswa = Siswa::find($id);

    if (!$siswa) {
      return response()->json(['message' => 'Siswa tidak ditemukan.'], 404);
    }

    $registrasi = SiswaRegistrasi::where('sekolah_id', $sekolahId)
      ->where('siswa_id', $siswa->id)
      ->orderByDesc('id')
      ->first();

    if (!$registrasi) {
      return response()->json([
        'message' => 'Siswa ini tidak terdaftar di sekolah Anda.',
      ], 403);
    }

    $semester = $this->semesterUntuk($request);
    $anggota = $semester
      ? RombelAnggota::with('rombel')
          ->where('siswa_id', $siswa->id)
          ->whereHas('rombel', fn($q) => $q->where('sekolah_id', $sekolahId)->where('semester_id', $semester->id))
          ->first()
      : null;

    return response()->json([
      'siswa' => [
        'siswa_id' => $siswa->id,
        'nama_siswa' => $siswa->nama_siswa,
        'nisn' => $siswa->nisn,
        'nis' => $registrasi->nis,
        'jenis_kelamin' => $siswa->jenis_kelamin,
        'tempat_lahir' => $siswa->tempat_lahir,
        'tgl_lahir' => $siswa->tgl_lahir,
        'agama' => $siswa->agama,
        'alamat' => $siswa->alamat,
        'disabilitas' => $siswa->disabilitas,
        'photo' => $siswa->photo,
        'nama_ayah' => $siswa->nama_ayah,
        'nama_ibu' => $siswa->nama_ibu,
        'nama_wali' => $siswa->nama_wali,
        'no_hp' => $siswa->no_hp,
        'status_registrasi' => $registrasi->status ?? null,
        'tanggal_diterima' => $registrasi->tanggal_diterima,
        'rombel' => $anggota ? [
          'id' => $anggota->rombel->id,
          'nama_rombel' => $anggota->rombel->nama_rombel,
          'tingkat' => $anggota->rombel->tingkat,
        ] : null,
      ],
    ]);
  }
}
