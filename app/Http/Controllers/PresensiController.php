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
    // Hanya rombel semester sesi: tanggal presensi divalidasi
    // terhadap semester sesi, jadi rombel semester lain tidak
    // boleh bisa dipilih (snapshot rombel/semester akan bertentangan)
    $rombelList = Rombel::where('semester_id', session('semester_id'))
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get();
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

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'success' => false,
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    // Roster = anggota rombel itu pada semesternya, dibatasi hari
    // efektif siswa dari tanggal registrasinya di sekolah rombel
    // ini: belum bisa dipresensi sebelum tanggal_diterima dan tidak
    // lagi sesudah tanggal_keluar. Keanggotaan rombel sendiri tidak
    // bertanggal — ia berlaku untuk semester rombel itu, jadi guru
    // rombel yang terdaftar pada semester tanggal inilah yang
    // mengelola presensinya.
    $anggota = $this->roster($rombel, $tanggal);

    // Presensi melekat ke siswa + tanggal, bukan ke rombel: status
    // tersimpan dicari tanpa memandang rombel asalnya, supaya
    // membuka tanggal yang sama dari rombel lain (mis. setelah
    // siswa pindah rombel) tetap menampilkan status yang tersimpan
    $presensiExisting = Presensi::whereIn('siswa_id', $anggota->pluck('siswa_id'))
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
   * Roster presensi: anggota rombel yang hari efektifnya — dari
   * tanggal registrasi di sekolah rombel itu — mencakup $tanggal
   * (belum bisa dipresensi sebelum tanggal_diterima, tidak lagi
   * sesudah tanggal_keluar). Dipakai bersama oleh ajaxSiswa dan
   * store() supaya aturan yang ditampilkan sama dengan yang
   * ditegakkan saat menyimpan.
   */
  private function roster(Rombel $rombel, Carbon $tanggal)
  {
    return RombelAnggota::with('siswa')
      ->where('rombel_id', $rombel->id)
      ->whereHas('siswa', function ($q) use ($rombel, $tanggal) {
        $q->whereHas('siswaRegistrasi', function ($qr) use ($rombel, $tanggal) {
          $qr->where('sekolah_id', $rombel->sekolah_id)
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_diterima')
                ->orWhereDate('tanggal_diterima', '<=', $tanggal);
            })
            ->where(function ($qw) use ($tanggal) {
              $qw->whereNull('tanggal_keluar')
                ->orWhereDate('tanggal_keluar', '>=', $tanggal);
            });
        });
      })
      ->get();
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
    $isEditable = Auth::user()->hasRole('Admin') || $semester->is_active == 1;

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

    $rombel = Rombel::findOrFail($request->rombel_id);

    if ((int) $rombel->semester_id !== (int) $semester->id) {
      return response()->json([
        'success' => false,
        'message' => 'Rombel ini bukan rombel semester ' . $semester->tahun_pelajaran . ' - ' . $semester->semester . '.',
      ], 422);
    }

    // Tegakkan aturan roster yang sama dengan yang ditampilkan
    // ajaxSiswa: setiap siswa yang dikirim harus anggota rombel ini
    // dan hari efektif registrasinya mencakup tanggal presensi.
    // Tanpa ini, POST langsung bisa menyimpan presensi untuk
    // non-anggota, sebelum tanggal_diterima, atau sesudah
    // tanggal_keluar.
    $rosterIds = $this->roster($rombel, $tanggal)->pluck('siswa_id');
    $diLuarRoster = collect($request->presensi)
      ->pluck('siswa_id')
      ->map(fn($id) => (int) $id)
      ->diff($rosterIds->map(fn($id) => (int) $id));

    if ($diLuarRoster->isNotEmpty()) {
      return response()->json([
        'success' => false,
        'message' => 'Ada ' . $diLuarRoster->count() . ' siswa di luar daftar presensi rombel ini untuk tanggal tersebut — presensi ditolak.',
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
