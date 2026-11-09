<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Presensi;
use App\Models\PresensiPengaturan;
use App\Models\Rombel;
use App\Models\Semester;
use App\Support\PresensiLayanan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * API presensi untuk aplikasi mobile guru. Semua aturan (hak
 * mengisi, jendela koreksi, penolakan tanggal, roster) memakai
 * App\Support\PresensiLayanan yang sama dengan halaman web —
 * tidak ada aturan versi mobile yang berbeda.
 *
 * Konteks semester: tanpa sesi seperti web, semester diambil
 * dari parameter semester_id bila dikirim, jika tidak dari
 * rombel yang dibuka, dan untuk daftar rombel dari semester
 * aktif.
 */
class PresensiApiController extends Controller
{
  private function semesterDari(Request $request, ?Rombel $rombel = null): ?Semester
  {
    if ($request->filled('semester_id')) {
      return Semester::find($request->semester_id);
    }

    if ($rombel) {
      return $rombel->semester;
    }

    return Semester::where('is_active', true)->first();
  }

  private function ringkasanSemester(?Semester $semester): ?array
  {
    if (!$semester) {
      return null;
    }

    return [
      'id' => $semester->id,
      'tahun_pelajaran' => $semester->tahun_pelajaran,
      'semester' => $semester->semester,
      'tanggal_mulai' => $semester->tanggal_mulai?->format('Y-m-d'),
      'tanggal_selesai' => $semester->tanggal_selesai?->format('Y-m-d'),
      'is_active' => (bool) $semester->is_active,
    ];
  }

  /** Daftar rombel yang boleh dilihat pengguna ini pada satu semester. */
  public function rombelSaya(Request $request)
  {
    $user = $request->user();
    $semester = $this->semesterDari($request);

    if (!$semester) {
      return response()->json([
        'message' => 'Semester tidak ditemukan. Sampaikan semester_id yang sah atau aktifkan satu semester.',
      ], 422);
    }

    $rombelList = Rombel::with(['sekolah.detailTerbaru', 'ptk'])
      ->where('semester_id', $semester->id)
      ->when(
        $user->hasRole('Guru') && !$user->hasAnyRole(['Operator Sekolah', 'Admin']),
        fn($q) => $q->where('ptk_id', $user->ptk?->id)
      )
      ->when(
        $user->hasAnyRole(['Operator Sekolah', 'Kepala Sekolah']),
        fn($q) => $q->where('sekolah_id', $user->ptk?->sekolah_id)
      )
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get();

    return response()->json([
      'semester' => $this->ringkasanSemester($semester),
      'rombel' => $rombelList->map(fn($r) => [
        'id' => $r->id,
        'nama_rombel' => $r->nama_rombel,
        'tingkat' => $r->tingkat,
        'sekolah_id' => $r->sekolah_id,
        'nama_sekolah' => $r->sekolah?->detailTerbaru?->nama_sekolah,
        'wali_kelas' => $r->ptk?->nama_ptk,
        'boleh_isi' => PresensiLayanan::bolehIsi($user, $r),
      ]),
    ]);
  }

  /** Roster + status tersimpan untuk satu rombel pada satu tanggal. */
  public function roster(Request $request)
  {
    $request->validate([
      'rombel_id' => ['required', 'exists:tabel_rombel,id'],
      'tanggal' => ['required', 'date'],
    ]);

    $user = $request->user();
    $tanggal = Carbon::parse($request->tanggal);
    $rombel = Rombel::findOrFail($request->rombel_id);
    $semester = $this->semesterDari($request, $rombel);

    if (!$semester) {
      return response()->json(['message' => 'Semester tidak ditemukan.'], 422);
    }

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    if ($libur = PresensiLayanan::liburPada($tanggal)) {
      return response()->json([
        'message' => 'Tanggal ini hari libur (' . $libur->kegiatan . '). Presensi tidak dapat diinput.',
      ], 422);
    }

    if ($pesan = PresensiLayanan::penolakanTanggal($rombel, $tanggal)) {
      return response()->json(['message' => $pesan], 422);
    }

    if (!PresensiLayanan::bolehLihat($user, $rombel)) {
      return response()->json(['message' => 'Anda tidak berhak melihat presensi rombel ini.'], 403);
    }

    $anggota = PresensiLayanan::roster($rombel, $tanggal);

    $presensiExisting = Presensi::with('inputOleh')
      ->whereIn('siswa_id', $anggota->pluck('siswa_id'))
      ->whereDate('tanggal', $tanggal)
      ->get()
      ->keyBy('siswa_id');

    $bolehIsi = PresensiLayanan::bolehIsi($user, $rombel)
      && (!$user->hasRole('Guru') || $user->hasRole('Operator Sekolah') || PresensiLayanan::dalamJendelaKoreksi($rombel, $tanggal));

    return response()->json([
      'semester' => $this->ringkasanSemester($semester),
      'rombel' => [
        'id' => $rombel->id,
        'nama_rombel' => $rombel->nama_rombel,
        'tingkat' => $rombel->tingkat,
      ],
      'tanggal' => $tanggal->format('Y-m-d'),
      'boleh_isi' => $bolehIsi,
      'siswa' => $anggota->map(function ($a) use ($presensiExisting) {
        $existing = $presensiExisting->get($a->siswa_id);

        return [
          'siswa_id' => $a->siswa->id,
          'nama_siswa' => $a->siswa->nama_siswa,
          'nisn' => $a->siswa->nisn,
          'photo' => $a->siswa->photo,
          'jenis_kelamin' => $a->siswa->jenis_kelamin,
          'status' => $existing->status ?? null,
          'keterangan' => $existing->keterangan ?? null,
          'terisi' => $existing !== null,
          'diisi_oleh' => $existing?->inputOleh?->name,
          'diubah_pada' => $existing?->updated_at?->format('d M Y H:i'),
        ];
      }),
    ]);
  }

  /** Simpan presensi satu rombel + tanggal (upsert per siswa + tanggal). */
  public function simpan(Request $request)
  {
    $request->validate([
      'rombel_id' => ['required', 'exists:tabel_rombel,id'],
      'tanggal' => ['required', 'date'],
      'presensi' => ['required', 'array', 'min:1'],
      'presensi.*.siswa_id' => ['required', 'exists:tabel_siswa,id'],
      'presensi.*.status' => ['required', 'in:hadir,izin,sakit,alpa'],
      'presensi.*.keterangan' => ['nullable', 'string'],
    ]);

    $user = $request->user();
    $tanggal = Carbon::parse($request->tanggal);
    $rombel = Rombel::findOrFail($request->rombel_id);
    $semester = $this->semesterDari($request, $rombel);

    if (!$semester) {
      return response()->json(['message' => 'Semester tidak ditemukan.'], 422);
    }

    if (!$semester->is_active && !$semester->kunci_dibuka) {
      return response()->json([
        'message' => 'Semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' sudah tidak aktif. Presensi tidak dapat diubah.',
      ], 403);
    }

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    if (PresensiLayanan::liburPada($tanggal)) {
      return response()->json(['message' => 'Tanggal ini hari libur, presensi tidak dapat disimpan.'], 422);
    }

    if ($pesan = PresensiLayanan::penolakanTanggal($rombel, $tanggal)) {
      return response()->json(['message' => $pesan], 422);
    }

    if (!PresensiLayanan::bolehIsi($user, $rombel)) {
      return response()->json([
        'message' => 'Anda tidak berhak mengisi presensi rombel ini. Hanya wali kelas rombel ini dan Operator Sekolah yang dapat mengisi.',
      ], 403);
    }

    if (
      $user->hasRole('Guru')
      && !$user->hasRole('Operator Sekolah')
      && !PresensiLayanan::dalamJendelaKoreksi($rombel, $tanggal)
    ) {
      $batas = PresensiPengaturan::batasUntuk((int) $rombel->sekolah_id);

      return response()->json([
        'message' => 'Tanggal ini sudah melewati batas koreksi presensi untuk Guru (' . $batas . ' hari). Silakan hubungi Operator Sekolah bila perlu perubahan.',
      ], 403);
    }

    $rosterIds = PresensiLayanan::roster($rombel, $tanggal)->pluck('siswa_id');
    $diLuarRoster = collect($request->presensi)
      ->pluck('siswa_id')
      ->map(fn($id) => (int) $id)
      ->diff($rosterIds->map(fn($id) => (int) $id));

    if ($diLuarRoster->isNotEmpty()) {
      return response()->json([
        'message' => 'Ada ' . $diLuarRoster->count() . ' siswa di luar daftar presensi rombel ini untuk tanggal tersebut — presensi ditolak.',
      ], 422);
    }

    DB::transaction(function () use ($request, $tanggal, $semester, $user) {
      $rows = [];

      foreach ($request->presensi as $item) {
        $rows[] = [
          'siswa_id' => $item['siswa_id'],
          'rombel_id' => $request->rombel_id,
          'semester_id' => $semester->id,
          'tanggal' => $tanggal->format('Y-m-d'),
          'status' => $item['status'],
          'keterangan' => $item['keterangan'] ?? null,
          'input_by' => $user->id,
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }

      Presensi::upsert(
        $rows,
        ['siswa_id', 'tanggal'],
        ['rombel_id', 'semester_id', 'status', 'keterangan', 'input_by', 'updated_at']
      );
    });

    return response()->json(['message' => 'Presensi berhasil disimpan.']);
  }
}
