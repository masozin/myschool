<?php

namespace App\Http\Controllers;

use App\Models\MapelKelompok;
use App\Models\MapelJenjang;
use App\Models\MapelMapping;
use App\Models\MapelMaster;
use App\Models\Sekolah;
use App\Models\Semester;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MapelMappingController extends Controller
{
  private function isAdmin(): bool
  {
    return Auth::user()->hasRole('Admin');
  }

  private function isOperator(): bool
  {
    return Auth::user()->hasRole('Operator Sekolah');
  }

  // ── Index ─────────────────────────────────────────────────────────────────
  public function index(Request $request)
  {
    $isAdmin    = $this->isAdmin();
    $isOperator = $this->isOperator();

    if (!$isAdmin && !$isOperator) {
      abort(403, 'Anda tidak memiliki akses ke fitur ini.');
    }

    $semesterList = Semester::orderByDesc('tahun_pelajaran')
      ->orderByDesc('semester')
      ->get();

    $semester = Semester::find(session('semester_id'));

    $jenjangList = ['SD', 'SMP', 'SMA', 'SMK'];

    if ($isOperator) {
      $sekolah        = Sekolah::findOrFail(Auth::user()->ptk->sekolah_id);
      $jenjangDipilih = strtoupper($sekolah->jenjang);
    } else {
      $jenjangDipilih = strtoupper($request->get('jenjang', 'SD'));
      $sekolah        = null;
    }

    $tingkatDipilih = (int) $request->get('tingkat');

    // Tingkat per jenjang
    $tingkatPerJenjang = [
      'SD'  => range(1, 6),
      'SMP' => range(7, 9),
      'SMA' => range(10, 12),
      'SMK' => range(10, 13),
    ];

    $tingkatList = $tingkatPerJenjang[$jenjangDipilih] ?? range(1, 12);

    // Jika tingkat yang dipilih tidak sesuai jenjang, reset ke tingkat pertama
    if (!in_array($tingkatDipilih, $tingkatList)) {
      $tingkatDipilih = $tingkatList[0] ?? 1;
    }

    $masterMapelList = MapelMaster::with('kelompok')
      ->aktifUntukJenjang($jenjangDipilih)
      ->orderBy('mapel_kelompok_id')
      ->orderBy('nama')
      ->get();

    $mapelKelompok = MapelKelompok::orderBy('urutan')->get(['id', 'nama']);

    return view('content.mapel-mapping.index', compact(
      'semesterList',
      'semester',
      'jenjangList',
      'jenjangDipilih',
      'tingkatDipilih',
      'tingkatList',
      'tingkatPerJenjang',
      'masterMapelList',
      'mapelKelompok',
      'isAdmin',
      'isOperator',
    ));
  }

  public function table(Request $request)
  {
    $isAdmin    = $this->isAdmin();
    $isOperator = $this->isOperator();

    if (!$isAdmin && !$isOperator) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $semester = Semester::findOrFail(session('semester_id'));

    if ($isOperator) {
      $sekolah        = Sekolah::findOrFail(Auth::user()->ptk->sekolah_id);
      $jenjangDipilih = strtoupper($sekolah->jenjang);
    } else {
      $jenjangDipilih = strtoupper($request->get('jenjang', 'SD'));
      $sekolah        = null;
    }

    $tingkatDipilih = (int) $request->get('tingkat');

    // Query MapelMapping (sama seperti sebelumnya)
    $query = MapelMapping::with([
      'masterMapel.kelompok',
      'masterMapel.sekolah',
    ])
      ->where('jenjang', $jenjangDipilih)
      ->where('semester_id', $semester->id);

    if ($tingkatDipilih >= 1 && $tingkatDipilih <= 12) {
      $query->where('tingkat', $tingkatDipilih);
    }

    if ($isOperator) {
      $query->where(function ($q) use ($sekolah) {
        $q->whereNull('sekolah_id')
          ->orWhere('sekolah_id', $sekolah->id);
      });
    } else {
      $query->whereNull('sekolah_id')
        ->where('jenis', 'pokok');
    }

    $mapels = $query
      ->orderByRaw("FIELD(jenis, 'pokok', 'tambahan')")
      ->orderBy('urutan')
      ->orderBy('id')
      ->get();

    // === PERUBAHAN UTAMA: Ambil Kelompok dari Data Mapping ===
    $kelompokDiMapping = $mapels->pluck('masterMapel.kelompok')
      ->filter()                    // buang null
      ->unique('id')                // unik berdasarkan id
      ->sortBy('urutan');           // urutkan sesuai urutan kelompok

    // Grouping mapel per kelompok
    $mapelPerKelompok = $mapels->groupBy(function ($m) {
      return $m->masterMapel?->kelompok?->nama ?? 'Tidak Ada Kelompok';
    });

    // Susun result
    $result = [];

    foreach ($kelompokDiMapping as $kelompok) {
      $namaKelompok = $kelompok->nama;
      $items = $mapelPerKelompok[$namaKelompok] ?? collect();

      $rows = [];
      foreach ($items as $i => $mapel) {
        $canEdit = ($isAdmin && $mapel->jenis === 'pokok')
          || ($isOperator && $mapel->jenis === 'tambahan');

        $rows[] = [
          'id'          => $mapel->id,
          'urutan'      => $mapel->urutan,
          'tingkat'     => $mapel->tingkat,
          'nama'        => $mapel->masterMapel?->nama ?? '—',
          'nama_sub'    => $mapel->masterMapel?->nama_sub_mapel ?? null,
          'jenis'       => $mapel->jenis,
          'master_id'   => $mapel->mapel_master_id,
          'can_edit'    => $canEdit,
          'is_first'    => $i === 0,
          'is_last'     => $i === $items->count() - 1,
        ];
      }

      $result[] = [
        'kelompok'  => $namaKelompok,
        'jenjang'   => $jenjangDipilih,
        'count'     => $items->count(),
        'rows'      => $rows,
      ];
    }

    return response()->json([
      'jenjang'  => $jenjangDipilih,
      'tingkat'  => $tingkatDipilih,
      'kelompok' => $result,
    ]);
  }

  // ── Store ─────────────────────────────────────────────────────────────────
  public function store(Request $request)
  {
    $isAdmin    = $this->isAdmin();
    $isOperator = $this->isOperator();

    if (!$isAdmin && !$isOperator) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
      'mapel_master_id' => 'required|exists:tabel_mapel_master,id',
      'tingkat'         => 'required|integer|min:1|max:12',
      'jenjang'         => $isOperator ? 'nullable|in:SD,SMP,SMA,SMK' : 'required|in:SD,SMP,SMA,SMK',
    ]);

    $semester = session('semester_id');

    if ($isOperator) {
      $sekolah    = Sekolah::findOrFail(Auth::user()->ptk->sekolah_id);
      $jenjang    = strtoupper($sekolah->jenjang);
      $jenis      = 'tambahan';
      $sekolah_id = $sekolah->id;
    } else {
      $jenjang    = $request->jenjang;
      $jenis      = 'pokok';
      $sekolah_id = null;
    }

    if (!$this->mapelValidUntukKonteks($request->mapel_master_id, $jenjang, $request->tingkat, $sekolah_id)) {
      return response()->json(['message' => 'Mata pelajaran tidak valid untuk jenjang/sekolah ini.'], 422);
    }

    // Cegah duplikat mapping untuk mapel + tingkat + semester yang sama
    $sudahAda = MapelMapping::where('mapel_master_id', $request->mapel_master_id)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $request->tingkat)
      ->where('semester_id', $semester)
      ->where('sekolah_id', $sekolah_id)
      ->exists();

    if ($sudahAda) {
      return response()->json([
        'message' => 'Mata pelajaran ini sudah ada untuk tingkat dan semester yang dipilih.',
      ], 422);
    }

    // Urutan: ambil max urutan dalam kelompok yang sama
    $mapelMaster = MapelMaster::findOrFail($request->mapel_master_id);
    $urutanMax   = MapelMapping::whereHas('masterMapel', function ($q) use ($mapelMaster) {
      $q->where('mapel_kelompok_id', $mapelMaster->mapel_kelompok_id);
    })
      ->where('jenjang', $jenjang)
      ->where('tingkat', $request->tingkat)
      ->where('semester_id', $semester)
      ->where('sekolah_id', $sekolah_id)
      ->max('urutan') ?? 0;

    MapelMapping::create([
      'mapel_master_id' => $request->mapel_master_id,
      'jenjang'         => $jenjang,
      'tingkat'         => $request->tingkat,
      'semester_id'     => $semester,
      'jenis'           => $jenis,
      'sekolah_id'      => $sekolah_id,
      'urutan'          => $urutanMax + 1,
    ]);

    return response()->json(['message' => 'Mata pelajaran berhasil ditambahkan.']);
  }

  // ── Update ────────────────────────────────────────────────────────────────
  public function update(Request $request, MapelMapping $mapel)
  {
    $isAdmin    = $this->isAdmin();
    $isOperator = $this->isOperator();

    // Validasi Akses
    if ($isOperator) {
      if ($mapel->jenis !== 'tambahan' || $mapel->sekolah_id !== Auth::user()->ptk->sekolah_id) {
        return response()->json(['message' => 'Anda hanya dapat mengedit mapel tambahan milik sekolah Anda.'], 403);
      }
    } elseif ($isAdmin) {
      if ($mapel->jenis !== 'pokok' || $mapel->sekolah_id !== null) {
        return response()->json(['message' => 'Admin hanya dapat mengedit mapel pokok.'], 403);
      }
    } else {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
      'mapel_master_id' => 'required|exists:tabel_mapel_master,id',
      'tingkat'         => 'required|integer|min:1|max:12',
    ]);

    if (!$this->mapelValidUntukKonteks($request->mapel_master_id, $mapel->jenjang, $request->tingkat, $mapel->sekolah_id)) {
      return response()->json(['message' => 'Mata pelajaran tidak valid untuk jenjang/sekolah ini.'], 422);
    }

    $mapel->update([
      'mapel_master_id' => $request->mapel_master_id,
      'tingkat'         => $request->tingkat,
    ]);

    return response()->json(['message' => 'Mata pelajaran berhasil diperbarui.']);
  }

  // ── Destroy ───────────────────────────────────────────────────────────────
  public function destroy(MapelMapping $mapel)
  {
    $isAdmin    = $this->isAdmin();
    $isOperator = $this->isOperator();

    if ($isOperator) {
      if ($mapel->jenis !== 'tambahan' || $mapel->sekolah_id !== Auth::user()->ptk->sekolah_id) {
        return response()->json(['message' => 'Anda hanya dapat menghapus mapel tambahan milik sekolah Anda.'], 403);
      }
    } elseif ($isAdmin) {
      if ($mapel->jenis !== 'pokok' || $mapel->sekolah_id !== null) {
        return response()->json(['message' => 'Admin hanya dapat menghapus mapel pokok.'], 403);
      }
    } else {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    // Simpan info sebelum delete untuk rapikan urutan
    $sekolah_id      = $mapel->sekolah_id;
    $jenjang         = $mapel->jenjang;
    $tingkat         = $mapel->tingkat;
    $semester_id     = $mapel->semester_id;
    $kelompok_id     = $mapel->masterMapel?->mapel_kelompok_id;

    $mapel->delete();

    $this->rapikanUrutan($sekolah_id, $jenjang, $tingkat, $semester_id, $kelompok_id);

    return response()->json(['message' => 'Mata pelajaran berhasil dihapus.']);
  }

  // ── Reorder (bulk) ────────────────────────────────────────────────────────
  public function reorder(Request $request)
  {
    $request->validate([
      'ids'   => 'required|array|min:1',
      'ids.*' => 'integer|exists:tabel_mapel_mapping,id',
    ]);

    $ids = $request->input('ids');

    // Pastikan semua ID milik user yang berhak
    $mapels = MapelMapping::whereIn('id', $ids)->get()->keyBy('id');

    foreach ($mapels as $mapel) {
      if (!$this->cekAkses($mapel)) {
        return response()->json(['message' => 'Akses ditolak.'], 403);
      }
    }

    // Update urutan sesuai posisi array
    DB::transaction(function () use ($ids, $mapels) {
      foreach ($ids as $urutan => $id) {
        if (isset($mapels[$id])) {
          $mapels[$id]->update(['urutan' => $urutan + 1]);
        }
      }
    });

    return response()->json(['message' => 'Urutan berhasil disimpan.']);
  }

  // ── Salin Semester ────────────────────────────────────────────────────────
  public function salinSemester(Request $request)
  {
    if (!$this->isAdmin()) {
      return response()->json(['message' => 'Hanya admin yang dapat menyalin mapel pokok.'], 403);
    }

    $request->validate([
      'jenjang'        => 'required|in:SD,SMP,SMA,SMK',
      'dari_semester'  => 'required|exists:tabel_semester,id',
      'ke_semester'    => 'required|exists:tabel_semester,id|different:dari_semester',
    ]);

    $jenjang       = $request->jenjang;
    $dariSemesterId = $request->dari_semester;
    $keSemesterId   = $request->ke_semester;

    // Cek apakah sudah ada mapel pokok di semester tujuan
    $sudahAda = MapelMapping::where('jenjang', $jenjang)
      ->where('semester_id', $keSemesterId)
      ->whereNull('sekolah_id')
      ->where('jenis', 'pokok')
      ->exists();

    if ($sudahAda) {
      $keSemester = Semester::find($keSemesterId);
      return response()->json([
        'message' => "Semester {$keSemester->tahun_pelajaran} — {$keSemester->semester} untuk jenjang {$jenjang} sudah memiliki data mapel. Hapus dulu sebelum menyalin.",
      ], 422);
    }

    $mapelSumber = MapelMapping::where('jenjang', $jenjang)
      ->where('semester_id', $dariSemesterId)
      ->whereNull('sekolah_id')
      ->where('jenis', 'pokok')
      ->orderBy('urutan')
      ->get();

    if ($mapelSumber->isEmpty()) {
      $dariSemester = Semester::find($dariSemesterId);
      return response()->json([
        'message' => "Tidak ada mapel pokok untuk jenjang {$jenjang} pada semester {$dariSemester->tahun_pelajaran} — {$dariSemester->semester}.",
      ], 422);
    }

    DB::beginTransaction();
    try {
      foreach ($mapelSumber as $m) {
        MapelMapping::create([
          'mapel_master_id' => $m->mapel_master_id,
          'jenjang'         => $jenjang,
          'tingkat'         => $m->tingkat,
          'semester_id'     => $keSemesterId,
          'jenis'           => 'pokok',
          'sekolah_id'      => null,
          'urutan'          => $m->urutan,
        ]);
      }
      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['message' => 'Gagal menyalin: ' . $e->getMessage()], 500);
    }

    return response()->json([
      'message' => "{$mapelSumber->count()} mata pelajaran berhasil disalin.",
    ]);
  }

    // ── Private Helpers ───────────────────────────────────────────────────────

  /**
   * Cek hak akses edit/urutan berdasarkan role dan kepemilikan mapel.
   */
  private function cekAkses(MapelMapping $mapel): bool
  {
    if ($this->isOperator()) {
      return $mapel->jenis === 'tambahan'
        && $mapel->sekolah_id === Auth::user()->ptk->sekolah_id;
    }

    if ($this->isAdmin()) {
      return $mapel->jenis === 'pokok' && $mapel->sekolah_id === null;
    }

    return false;
  }

  /**
   * Rapikan urutan setelah hapus.
   */
  private function rapikanUrutan(
    ?int $sekolah_id,
    string $jenjang,
    int $tingkat,
    int $semester_id,
    ?int $kelompok_id
  ): void {
    $query = MapelMapping::where('jenjang', $jenjang)
      ->where('tingkat', $tingkat)
      ->where('semester_id', $semester_id)
      ->where('sekolah_id', $sekolah_id)
      ->orderBy('urutan');

    if ($kelompok_id) {
      $query->whereHas('masterMapel', function ($q) use ($kelompok_id) {
        $q->where('mapel_kelompok_id', $kelompok_id);
      });
    }

    foreach ($query->get() as $i => $m) {
      $m->update(['urutan' => $i + 1]);
    }
  }

  // ── Helper: validasi mapel_master_id sesuai konteks jenjang/sekolah ────────
  private function mapelValidUntukKonteks(int $mapelMasterId, string $jenjang, int $tingkat, ?int $sekolahId): bool
  {
    $query = MapelMaster::where('id', $mapelMasterId)
      ->where('is_aktif', true)
      ->whereHas('mapelJenjang', function ($q) use ($jenjang, $tingkat) {
        $q->where('jenjang', $jenjang)
          ->where(function ($sub) use ($tingkat) {
            $sub->whereNull('kelas_mulai')->orWhere('kelas_mulai', '<=', $tingkat);
          })
          ->where(function ($sub) use ($tingkat) {
            $sub->whereNull('kelas_selesai')->orWhere('kelas_selesai', '>=', $tingkat);
          });
      });

    if ($sekolahId) {
      $query->where(function ($q) use ($sekolahId) {
        $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
      });
    } else {
      $query->whereNull('sekolah_id');
    }

    return $query->exists();
  }

  // ── Quick Create: mapel lokal langsung dari halaman mapping ────────────────
  public function quickCreateMapel(Request $request)
  {
    if (!$this->isOperator()) {
      return response()->json(['message' => 'Hanya Operator Sekolah yang dapat menambahkan mapel lokal.'], 403);
    }

    $ptk = Auth::user()->ptk;
    if (!$ptk || !$ptk->sekolah_id) {
      return response()->json(['message' => 'Profil PTK/sekolah Anda tidak ditemukan.'], 422);
    }

    $sekolah = Sekolah::findOrFail($ptk->sekolah_id);
    $jenjang = strtoupper($sekolah->jenjang);

    $tingkatPerJenjang = [
      'SD'  => range(1, 6),
      'SMP' => range(7, 9),
      'SMA' => range(10, 12),
      'SMK' => range(10, 13),
    ];
    $tingkatValid = $tingkatPerJenjang[$jenjang] ?? [];

    if (empty($tingkatValid)) {
      return response()->json(['message' => 'Jenjang sekolah Anda tidak dikenali.'], 422);
    }

    $request->validate([
      'mapel_kelompok_id' => 'required|exists:tabel_mapel_kelompok,id',
      'nama'              => 'required|string|max:100',
      'nama_singkat'      => 'nullable|string|max:20',
      'kelas_mulai'       => 'required|integer|in:' . implode(',', $tingkatValid),
      'kelas_selesai'     => 'required|integer|in:' . implode(',', $tingkatValid) . '|gte:kelas_mulai',
    ]);

    $semesterId = session('semester_id');
    if (!$semesterId) {
      return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 422);
    }

    // Cegah duplikat nama mapel lokal di sekolah yang sama
    $duplikat = MapelMaster::where('sekolah_id', $sekolah->id)
      ->whereRaw('LOWER(nama) = ?', [strtolower(trim($request->nama))])
      ->exists();

    if ($duplikat) {
      return response()->json([
        'message' => 'Mata pelajaran lokal dengan nama ini sudah ada di sekolah Anda.',
      ], 422);
    }

    $tingkatList = range($request->kelas_mulai, $request->kelas_selesai);

    DB::beginTransaction();
    try {
      $mapelMaster = MapelMaster::create([
        'mapel_kelompok_id' => $request->mapel_kelompok_id,
        'nama'              => trim($request->nama),
        'nama_singkat'      => $request->nama_singkat ? trim($request->nama_singkat) : null,
        'sekolah_id'        => $sekolah->id,
        'is_aktif'          => true,
      ]);

      $mapelMaster->mapelJenjang()->create([
        'jenjang'       => $jenjang,
        'kelas_mulai'   => $request->kelas_mulai,
        'kelas_selesai' => $request->kelas_selesai,
      ]);

      foreach ($tingkatList as $tingkat) {
        $urutanMax = MapelMapping::whereHas('masterMapel', function ($q) use ($request) {
          $q->where('mapel_kelompok_id', $request->mapel_kelompok_id);
        })
          ->where('jenjang', $jenjang)
          ->where('tingkat', $tingkat)
          ->where('semester_id', $semesterId)
          ->where('sekolah_id', $sekolah->id)
          ->max('urutan') ?? 0;

        MapelMapping::create([
          'mapel_master_id' => $mapelMaster->id,
          'jenjang'         => $jenjang,
          'tingkat'         => $tingkat,
          'semester_id'     => $semesterId,
          'jenis'           => 'tambahan',
          'sekolah_id'      => $sekolah->id,
          'urutan'          => $urutanMax + 1,
        ]);
      }

      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      return response()->json(['message' => 'Gagal menambahkan mapel lokal: ' . $e->getMessage()], 500);
    }

    return response()->json([
      'message' => 'Mata pelajaran lokal berhasil ditambahkan dan dipetakan ke ' . count($tingkatList) . ' tingkat.',
      'data'    => $mapelMaster->load('mapelJenjang'),
    ], 201);
  }
}
