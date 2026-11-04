<?php

namespace App\Http\Controllers;

use App\Models\Presensi;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\Semester;
use App\Models\KalenderPendidikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PresensiController extends Controller
{
  /**
   * Halaman utama presensi (blade biasa).
   * Hanya menyiapkan dropdown rombel, grid siswa di-load via AJAX.
   */
  public function index()
  {
    $rombelList = Rombel::orderBy('nama_rombel')->get();
    $semester   = Semester::find(session('semester_id'));

    return view('content.presensi.index', compact('rombelList', 'semester'));
  }

  /**
   * AJAX: ambil daftar siswa + status presensi (jika ada) untuk rombel & tanggal tertentu.
   * Dipanggil dari JS saat user klik "Tampilkan".
   */
  public function ajaxSiswa(Request $request)
  {
    $request->validate([
      'rombel_id' => 'required|exists:tabel_rombel,id',
      'tanggal'   => 'required|date',
    ]);

    $rombelId = $request->rombel_id;
    $tanggal  = Carbon::parse($request->tanggal);

    // Ambil semester dari session (di-set saat login / SemesterSessionMiddleware)
    $semester = Semester::find(session('semester_id'));

    if (!$semester) {
      return response()->json([
        'success' => false,
        'message' => 'Semester aktif tidak ditemukan di sesi. Silakan login ulang.',
      ], 422);
    }

    // Tanggal tidak boleh di luar rentang semester yang sedang dipilih
    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    // Cek hari libur
    $libur = KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->first();

    if ($libur) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal ini hari libur (' . $libur->kegiatan . '). Presensi tidak dapat diinput.',
      ], 422);
    }

    $rombel = Rombel::findOrFail($rombelId);

    $anggota = RombelAnggota::with('siswa')
      ->where('rombel_id', $rombelId)
      ->whereDate('start_date', '<=', $tanggal)
      ->where(function ($q) use ($tanggal) {
        $q->whereNull('end_date')
          ->orWhereDate('end_date', '>=', $tanggal);
      })
      ->get();

    $presensiExisting = Presensi::where('rombel_id', $rombelId)
      ->whereDate('tanggal', $tanggal)
      ->get()
      ->keyBy('siswa_id');

    $data = $anggota->map(function ($a) use ($presensiExisting) {
      $existing = $presensiExisting->get($a->siswa_id);

      return [
        'siswa_id'   => $a->siswa->id,
        'nama_siswa' => $a->siswa->nama_siswa,
        'nisn'       => $a->siswa->nisn,
        'status'     => $existing->status ?? Presensi::STATUS_HADIR,
        'keterangan' => $existing->keterangan ?? null,
      ];
    });

    return response()->json([
      'success' => true,
      'rombel'  => [
        'id'          => $rombel->id,
        'nama_rombel' => $rombel->nama_rombel,
      ],
      'tanggal' => $tanggal->format('Y-m-d'),
      'siswa'   => $data,
    ]);
  }

  /**
   * AJAX: simpan/update presensi sekaligus untuk satu rombel + tanggal.
   * Boleh dipanggil berulang (edit kapan saja) -> upsert berdasarkan (siswa_id, tanggal).
   */
  public function store(Request $request)
  {
    $request->validate([
      'rombel_id' => 'required|exists:tabel_rombel,id',
      'tanggal'   => 'required|date',
      'presensi'  => 'required|array|min:1',
      'presensi.*.siswa_id'   => 'required|exists:tabel_siswa,id',
      'presensi.*.status'     => 'required|in:hadir,izin,sakit,alpa',
      'presensi.*.keterangan' => 'nullable|string',
    ]);

    $tanggal = Carbon::parse($request->tanggal);

    $semester = Semester::find(session('semester_id'));

    if (!$semester) {
      return response()->json([
        'success' => false,
        'message' => 'Semester aktif tidak ditemukan di sesi. Silakan login ulang.',
      ], 422);
    }

    // Sama seperti modul lain: kalau semester di session bukan semester aktif,
    // hanya admin yang boleh melakukan perubahan (termasuk presensi).
    $isEditable = Auth::user()->hasRole('admin') || $semester->is_active == 1;

    if (!$isEditable) {
      return response()->json([
        'success' => false,
        'message' => 'Semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' sudah tidak aktif. Presensi tidak dapat diubah.',
      ], 403);
    }

    if ($tanggal->lt($semester->tanggal_mulai) || $tanggal->gt($semester->tanggal_selesai)) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal di luar rentang semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester
          . ' (' . $semester->tanggal_mulai->format('d-m-Y') . ' s/d ' . $semester->tanggal_selesai->format('d-m-Y') . ').',
      ], 422);
    }

    $libur = KalenderPendidikan::where('is_libur', true)
      ->whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->exists();

    if ($libur) {
      return response()->json([
        'success' => false,
        'message' => 'Tanggal ini hari libur, presensi tidak dapat disimpan.',
      ], 422);
    }

    DB::transaction(function () use ($request, $tanggal, $semester) {
      $rows = [];

      foreach ($request->presensi as $item) {
        $rows[] = [
          'siswa_id'    => $item['siswa_id'],
          'rombel_id'   => $request->rombel_id,
          'semester_id' => $semester->id,
          'tanggal'     => $tanggal->format('Y-m-d'),
          'status'      => $item['status'],
          'keterangan'  => $item['keterangan'] ?? null,
          'input_by'    => Auth::id(),
          'created_at'  => now(),
          'updated_at'  => now(),
        ];
      }

      Presensi::upsert(
        $rows,
        ['siswa_id', 'tanggal'],
        ['rombel_id', 'semester_id', 'status', 'keterangan', 'input_by', 'updated_at']
      );
    });

    return response()->json([
      'success' => true,
      'message' => 'Presensi berhasil disimpan.',
    ]);
  }
}
