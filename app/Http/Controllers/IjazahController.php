<?php

namespace App\Http\Controllers;

use App\Models\Ijazah;
use App\Models\IjazahKomponen;
use App\Models\IjazahKonfigurasi;
use App\Models\IjazahNilai;
use App\Models\IjazahNilaiKomponen;
use App\Models\IjazahNilaiSubKomponen;
use App\Models\MapelMapping;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Diakses oleh Operator Sekolah dan Guru Walikelas.
 * Mengelola dokumen SKL (Surat Keterangan Lulus) + input nilai per siswa.
 * Mode input (jadi/manual) diambil dari IjazahKonfigurasi sekolah.
 *
 * Index menampilkan SEMUA siswa kelas akhir (6/9/12) beserta status SKL-nya.
 *
 * View: semua halaman siswa → satu file content.ijazah.show
 *       dibedakan lewat $viewMode: 'create' | 'edit' | 'show'
 */
class IjazahController extends Controller
{
  /**
   * Jenjang → tingkat kelas akhir.
   */
  private const KELAS_AKHIR = [
    'SD'  => 6,
    'SMP' => 9,
    'SMA' => 12,
    'SMK' => 12,
  ];

  /**
   * Ambil sekolah_id user yang sedang login via relasi PTK.
   */
  private function getSekolahId(): int
  {
    $sekolahId = Auth::user()->ptk?->sekolah_id;
    abort_if(!$sekolahId, 403, 'Akun Anda belum terhubung ke sekolah.');
    return $sekolahId;
  }

  /**
   * Ambil tingkat kelas akhir berdasarkan jenjang sekolah.
   */
  private function getKelasAkhir(int $sekolahId): int
  {
    $jenjang = \App\Models\Sekolah::find($sekolahId)?->jenjang ?? 'SMA';
    return self::KELAS_AKHIR[$jenjang] ?? 12;
  }

    // ── Index ─────────────────────────────────────────────────────────────

  /**
   * Tampilkan semua siswa kelas akhir beserta status SKL-nya.
   * Siswa yang belum punya SKL ditampilkan dengan status 'belum'.
   */
  public function index(Request $request)
  {
    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();
    $semester    = Semester::find($semesterId);
    $konfigurasi = IjazahKonfigurasi::firstOrDefault($sekolahId, (int) $semesterId);
    $kelasAkhir  = $this->getKelasAkhir($sekolahId);

    // Ambil semua siswa kelas akhir
    $siswaQuery = Siswa::whereHas('rombelAnggota', function ($q) use ($semesterId, $sekolahId, $kelasAkhir) {
      $q->where('semester_id', $semesterId)
        ->whereHas(
          'rombel',
          fn($r) => $r
            ->where('sekolah_id', $sekolahId)
            ->where('tingkat', $kelasAkhir)
        );
    });

    if ($request->filled('search')) {
      $siswaQuery->where(function ($q) use ($request) {
        $q->where('nama_siswa', 'like', '%' . $request->search . '%')
          ->orWhere('nisn', 'like', '%' . $request->search . '%');
      });
    }

    $semuaSiswa = $siswaQuery->orderBy('nama_siswa')->get();

    // Map skl per siswa_id
    $sklMap = Ijazah::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->with(['nilais'])
      ->get()
      ->keyBy('siswa_id');

    // Filter status jika diminta
    if ($request->filled('status')) {
      if ($request->status === 'belum') {
        $semuaSiswa = $semuaSiswa->filter(fn($s) => !isset($sklMap[$s->id]));
      } else {
        $semuaSiswa = $semuaSiswa->filter(
          fn($s) =>
          isset($sklMap[$s->id]) && $sklMap[$s->id]->status === $request->status
        );
      }
    }

    // Statistik
    $statBelum     = $semuaSiswa->filter(fn($s) => !isset($sklMap[$s->id]))->count();
    $statDraft     = $sklMap->where('status', 'draft')->count();
    $statPengajuan = $sklMap->where('status', 'pengajuan')->count();
    $statDisetujui = $sklMap->where('status', 'disetujui')->count();
    $statDitolak   = $sklMap->where('status', 'ditolak')->count();

    return view('content.ijazah.index', compact(
      'semuaSiswa',
      'sklMap',
      'semester',
      'konfigurasi',
      'kelasAkhir',
      'statBelum',
      'statDraft',
      'statPengajuan',
      'statDisetujui',
      'statDitolak'
    ));
  }

  // ── Create ────────────────────────────────────────────────────────────

  public function create()
  {
    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();
    $semester    = Semester::findOrFail($semesterId);
    $konfigurasi = IjazahKonfigurasi::firstOrDefault($sekolahId, (int) $semesterId);
    $kelasAkhir  = $this->getKelasAkhir($sekolahId);

    $siswaSudahPunyaSKL = Ijazah::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->pluck('siswa_id');

    $siswaList = Siswa::whereHas('rombelAnggota', function ($q) use ($semesterId, $sekolahId, $kelasAkhir) {
      $q->where('semester_id', $semesterId)
        ->whereHas(
          'rombel',
          fn($r) => $r
            ->where('sekolah_id', $sekolahId)
            ->where('tingkat', $kelasAkhir)
        );
    })
      ->whereNotIn('id', $siswaSudahPunyaSKL)
      ->orderBy('nama_siswa')
      ->get();

    return view('content.ijazah.show', compact('siswaList', 'semester', 'konfigurasi'))
      ->with('viewMode', 'create');
  }

  public function store(Request $request)
  {
    $request->validate(['siswa_id' => 'required|exists:tabel_siswa,id']);

    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();
    $semester    = Semester::findOrFail($semesterId);
    $konfigurasi = IjazahKonfigurasi::firstOrDefault($sekolahId, (int) $semesterId);
    $modeInput   = $konfigurasi->mode_input;

    $sudahAda = Ijazah::where('siswa_id', $request->siswa_id)
      ->where('sekolah_id', $sekolahId)
      ->exists();

    if ($sudahAda) {
      return back()->withErrors(['siswa_id' => 'Siswa ini sudah memiliki data SKL.']);
    }

    DB::transaction(function () use ($request, $semesterId, $sekolahId, $semester, $modeInput, $konfigurasi) {
      $ijazah = Ijazah::create([
        'siswa_id'    => $request->siswa_id,
        'sekolah_id'  => $sekolahId,
        'semester_id' => $semesterId,
        'mode_input'  => $modeInput,
        'status'      => 'draft',
      ]);

      $konfigurasi->kunci();

      $mappings = MapelMapping::where('semester_id', $semesterId)
        ->where(function ($q) use ($sekolahId) {
          $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
        })
        ->orderBy('urutan')
        ->get();

      foreach ($mappings as $mapping) {
        $ijazahNilai = IjazahNilai::create([
          'ijazah_id'        => $ijazah->id,
          'mapel_mapping_id' => $mapping->id,
          'nilai_akhir'      => null,
        ]);

        if ($modeInput === 'manual') {
          $komponens = IjazahKomponen::untuk(
            $mapping->jenjang,
            $semester->tahun_pelajaran
          )->with('subKomponen')->get();

          foreach ($komponens as $komponen) {
            $ijazahNilaiKomponen = IjazahNilaiKomponen::create([
              'ijazah_nilai_id' => $ijazahNilai->id,
              'komponen_id'     => $komponen->id,
              'nilai'           => null,
            ]);

            foreach ($komponen->subKomponen as $sub) {
              IjazahNilaiSubKomponen::create([
                'ijazah_nilai_komponen_id' => $ijazahNilaiKomponen->id,
                'sub_komponen_id'          => $sub->id,
                'nilai'                    => null,
              ]);
            }
          }
        }
      }
    });

    return redirect()
      ->route('ijazah.index')
      ->with('success', 'Data SKL berhasil dibuat. Silakan input nilai.');
  }

  // ── Show ──────────────────────────────────────────────────────────────

  public function show(Ijazah $ijazah)
  {
    $ijazah->load(['siswa', 'sekolah.detailTerbaru', 'semester']);
    $nilais = $ijazah->nilaisLengkap();

    return view('content.ijazah.show', compact('ijazah', 'nilais'))
      ->with('viewMode', 'show');
  }

  // ── Edit Nilai ────────────────────────────────────────────────────────

  public function editNilai(Ijazah $ijazah)
  {
    abort_if(
      !in_array($ijazah->status, ['draft', 'ditolak']),
      403,
      'Nilai tidak dapat diubah pada status ' . $ijazah->status
    );

    $ijazah->load(['siswa', 'semester']);
    $nilais = $ijazah->nilaisLengkap();

    return view('content.ijazah.show', compact('ijazah', 'nilais'))
      ->with('viewMode', 'edit');
  }

  public function updateNilai(Request $request, Ijazah $ijazah)
  {
    abort_if(
      !in_array($ijazah->status, ['draft', 'ditolak']),
      403,
      'Nilai tidak dapat diubah pada status ' . $ijazah->status
    );

    DB::transaction(function () use ($request, $ijazah) {
      if ($ijazah->mode_input === 'jadi') {
        $this->simpanNilaiJadi($request, $ijazah);
      } else {
        $this->simpanNilaiManual($request, $ijazah);
      }
    });

    return response()->json(['message' => 'Nilai berhasil disimpan.']);
  }

    // ── Nomor SKL ─────────────────────────────────────────────────────────

  /**
   * Update nomor SKL dan/atau tanggal terbit via AJAX (modal inline di index).
   */
  public function updateNomorSkl(Request $request, Ijazah $ijazah)
  {
    abort_if($ijazah->status === 'disetujui' && !Auth::user()->hasRole('Admin'), 403);

    $data = $request->validate([
      'nomor_ijazah'   => 'nullable|string|max:100',
      'tanggal_terbit' => 'nullable|date',
    ]);

    $ijazah->update($data);

    if ($request->expectsJson()) {
      return response()->json([
        'message'      => 'Nomor SKL berhasil disimpan.',
        'nomor_ijazah' => $ijazah->nomor_ijazah,
      ]);
    }

    return back()->with('success', 'Nomor SKL diperbarui.');
  }

  // ── Update Header ─────────────────────────────────────────────────────

  public function edit(Ijazah $ijazah)
  {
    abort_if(!in_array($ijazah->status, ['draft', 'ditolak']), 403);
    return view('content.ijazah.edit', compact('ijazah'));
  }

  public function update(Request $request, Ijazah $ijazah)
  {
    abort_if(!in_array($ijazah->status, ['draft', 'ditolak']), 403);

    $data = $request->validate([
      'nomor_ijazah'   => 'nullable|string|max:100',
      'tanggal_terbit' => 'nullable|date',
    ]);

    $ijazah->update($data);

    return redirect()
      ->route('ijazah.show', $ijazah)
      ->with('success', 'Data SKL berhasil diperbarui.');
  }

  // ── Alur Status ───────────────────────────────────────────────────────

  public function ajukan(Ijazah $ijazah)
  {
    abort_if(!in_array($ijazah->status, ['draft', 'ditolak']), 403);

    if (!$ijazah->sudahLengkap()) {
      return response()->json([
        'message' => 'Semua nilai mapel harus diisi sebelum mengajukan.',
        'errors'  => ['nilai' => 'Semua nilai mapel harus diisi sebelum mengajukan.'],
      ], 422);
    }

    $ijazah->update(['status' => 'pengajuan']);

    return response()->json(['message' => 'SKL berhasil diajukan.']);
  }

  public function setujui(Ijazah $ijazah)
  {
    abort_if($ijazah->status !== 'pengajuan', 403);

    $ijazah->update(['status' => 'disetujui', 'generated_at' => now()]);

    return response()->json(['message' => 'SKL berhasil disetujui.']);
  }

  public function tolak(Request $request, Ijazah $ijazah)
  {
    abort_if($ijazah->status !== 'pengajuan', 403);

    $request->validate(['catatan' => 'required|string|max:500']);

    $ijazah->update(['status' => 'ditolak', 'catatan' => $request->catatan]);

    return response()->json(['message' => 'SKL dikembalikan untuk diperbaiki.']);
  }

  public function destroy(Ijazah $ijazah)
  {
    abort_if($ijazah->status === 'disetujui', 403, 'SKL yang sudah disetujui tidak dapat dihapus.');

    $ijazah->delete();

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Data SKL berhasil dihapus.']);
    }

    return redirect()->route('ijazah.index')->with('success', 'Data SKL dihapus.');
  }

  // ── Private Helpers ───────────────────────────────────────────────────

  private function simpanNilaiJadi(Request $request, Ijazah $ijazah): void
  {
    $rules = [];
    foreach ($ijazah->nilais as $ijazahNilai) {
      $rules["nilai.{$ijazahNilai->id}"] = 'nullable|numeric|min:0|max:100';
    }
    $request->validate($rules);

    foreach ($ijazah->nilais as $ijazahNilai) {
      $nilaiInput = $request->input("nilai.{$ijazahNilai->id}");
      $ijazahNilai->update([
        'nilai_akhir' => $nilaiInput,
        'nilai_huruf' => $nilaiInput !== null
          ? IjazahNilai::angkaKeHuruf((float) $nilaiInput)
          : null,
      ]);
    }
  }

  private function simpanNilaiManual(Request $request, Ijazah $ijazah): void
  {
    $rules = [];
    foreach ($ijazah->nilais as $ijazahNilai) {
      foreach ($ijazahNilai->komponens as $kn) {
        if ($kn->komponen->subKomponen->isNotEmpty()) {
          foreach ($kn->nilaiSubKomponen as $nsk) {
            $rules["sub.{$ijazahNilai->id}.{$kn->komponen_id}.{$nsk->sub_komponen_id}"]
              = 'nullable|numeric|min:0|max:100';
          }
        } else {
          $rules["nilai.{$ijazahNilai->id}.{$kn->komponen_id}"]
            = 'nullable|numeric|min:0|max:100';
        }
      }
    }
    $request->validate($rules);

    foreach ($ijazah->nilais as $ijazahNilai) {
      foreach ($ijazahNilai->komponens as $kn) {
        if ($kn->komponen->subKomponen->isNotEmpty()) {
          foreach ($kn->nilaiSubKomponen as $nsk) {
            $nsk->update([
              'nilai' => $request->input(
                "sub.{$ijazahNilai->id}.{$kn->komponen_id}.{$nsk->sub_komponen_id}"
              ),
            ]);
          }
          $kn->refresh()->hitungDariSub();
        } else {
          $kn->update([
            'nilai' => $request->input("nilai.{$ijazahNilai->id}.{$kn->komponen_id}"),
          ]);
        }
      }
      $ijazahNilai->refresh()->hitungNilaiAkhir();
    }
  }
}
