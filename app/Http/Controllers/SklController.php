<?php

namespace App\Http\Controllers;

use App\Imports\SklImport;
use App\Models\MapelMapping;
use App\Models\RombelAnggota;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Skl;
use App\Models\SklKomponen;
use App\Models\SklKonfigurasi;
use App\Models\SklNilai;
use App\Models\SklNilaiKomponen;
use App\Models\SklNilaiSubKomponen;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

/**
 * Diakses oleh Operator Sekolah dan Guru Walikelas.
 * Mengelola dokumen SKL (Surat Keterangan Lulus) + input nilai per siswa.
 * Mode input (jadi/manual) diambil dari SklKonfigurasi sekolah.
 *
 * Halaman index menampilkan SEMUA siswa kelas akhir (tingkat 6/9/12).
 * Siswa yang belum memiliki SKL ditampilkan dengan status 'belum'.
 *
 * View: semua halaman siswa → satu file content.skl.show
 *       dibedakan lewat $viewMode: 'create' | 'edit' | 'show'
 */
class SklController extends Controller
{

  // Tingkat akhir per jenjang
  private array $tingkatAkhir = [
    'SD'  => 6,
    'SMP' => 9,
    'SMA' => 12,
    'SMK' => 12,
  ];

  /**
   * Ambil sekolah_id user yang sedang login via relasi PTK.
   * Abort 403 jika user tidak terhubung ke PTK/sekolah.
   */
  private function getSekolahId(): int
  {
    $sekolahId = Auth::user()->ptk?->sekolah_id;
    abort_if(!$sekolahId, 403, 'Akun Anda belum terhubung ke sekolah.');
    return $sekolahId;
  }

  /**
   * Tingkat kelas akhir berdasarkan jenjang sekolah.
   * SD=6, SMP=9, SMA/SMK=12
   */
  private function getTingkatAkhir(int $sekolahId): int
  {
    $jenjang = \App\Models\Sekolah::find($sekolahId)?->jenjang ?? 'SMA';
    return match ($jenjang) {
      'SD'       => 6,
      'SMP'      => 9,
      default    => 12, // SMA, SMK
    };
  }

  // ── Index ─────────────────────────────────────────────────────────────

  public function index(Request $request)
  {
    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();
    $semester    = Semester::find($semesterId);
    $konfigurasi = SklKonfigurasi::untukSekolah($sekolahId, (int) $semesterId);

    if (!$konfigurasi) {
      return redirect()->route('skl.konfigurasi.edit')
        ->with('error', 'Silakan lengkapi konfigurasi SKL terlebih dahulu sebelum mengakses menu SKL.');
    }

    // $konfigurasi = SklKonfigurasi::firstOrDefault($sekolahId, (int) $semesterId);
    $tingkatAkhir = $this->getTingkatAkhir($sekolahId);

    // Ambil semua siswa kelas akhir
    $siswaQuery = Siswa::whereHas('rombelAnggota', function ($q) use ($semesterId, $sekolahId, $tingkatAkhir) {
      $q->where('semester_id', $semesterId)
        ->whereHas('rombel', function ($r) use ($sekolahId, $tingkatAkhir) {
          $r->where('sekolah_id', $sekolahId)
            ->where('tingkat', $tingkatAkhir);
        });
    })->orderBy('nama_siswa');

    if ($request->filled('search')) {
      $siswaQuery->where(function ($q) use ($request) {
        $q->where('nama_siswa', 'like', '%' . $request->search . '%')
          ->orWhere('nisn', 'like', '%' . $request->search . '%');
      });
    }

    $semuaSiswa = $siswaQuery->get();

    // Ambil SKL yang sudah ada untuk semester & sekolah ini
    // $sklExisting = Skl::with(['siswa', 'nilais'])
    //   ->where('sekolah_id', $sekolahId)
    //   ->where('semester_id', $semesterId)
    //   ->whereIn('siswa_id', $semuaSiswa->pluck('id'))
    //   ->get()
    //   ->keyBy('siswa_id');

    // Ambil SKL yang sudah ada untuk semester & sekolah ini (Eager Loading tingkat mendalam)
    $sklExisting = Skl::with([
      'siswa',
      'nilais.komponens.komponen.subKomponen',
      'nilais.komponens.nilaiSubKomponen'
    ])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->whereIn('siswa_id', $semuaSiswa->pluck('id'))
      ->get()
      ->keyBy('siswa_id');

    // Gabungkan: setiap siswa + SKL-nya (null jika belum ada)
    $daftarSiswa = $semuaSiswa->map(fn($siswa) => [
      'siswa' => $siswa,
      'skl'   => $sklExisting->get($siswa->id),
    ]);

    // Filter status jika diminta
    if ($request->filled('status')) {
      $filterStatus = $request->status;
      $daftarSiswa = $daftarSiswa->filter(function ($item) use ($filterStatus) {
        if ($filterStatus === 'belum') {
          return $item['skl'] === null;
        }
        return $item['skl']?->status === $filterStatus;
      });
    }

    // Stat
    $statBelum     = $semuaSiswa->count() - $sklExisting->count();
    $statDraft     = $sklExisting->where('status', 'draft')->count();
    $statPengajuan = $sklExisting->where('status', 'pengajuan')->count();
    $statDisetujui = $sklExisting->where('status', 'disetujui')->count();
    $statDitolak   = $sklExisting->where('status', 'ditolak')->count();

    return view('content.skl.index', compact(
      'daftarSiswa',
      'semester',
      'konfigurasi',
      'statBelum',
      'statDraft',
      'statPengajuan',
      'statDisetujui',
      'statDitolak',
      'tingkatAkhir',
    ));
  }

  // ── Show ──────────────────────────────────────────────────────────────

  public function show(Skl $skl)
  {
    $skl->load(['siswa', 'sekolah.detailTerbaru', 'semester']);
    $nilais = $skl->nilaisLengkap();

    return view('content.skl.show', compact('skl', 'nilais'))
      ->with('viewMode', 'show');
  }

  // ── Buat SKL baru untuk siswa (dipanggil dari tombol di index) ─────────

  public function store(Request $request)
  {
    $request->validate(['siswa_id' => 'required|exists:tabel_siswa,id']);

    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();
    $semester    = Semester::findOrFail($semesterId);

    // 1. Cek Ketersediaan Konfigurasi SKL
    $konfigurasi = SklKonfigurasi::untukSekolah($sekolahId, (int) $semesterId);

    if (!$konfigurasi) {
      $pesanError = "Konfigurasi SKL untuk semester ini belum diatur. Silakan atur konfigurasi terlebih dahulu.";
      if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
      return back()->with('error', $pesanError);
    }

    $modeInput   = $konfigurasi->mode_input;

    // Dapatkan Jenjang dan Tingkat Akhir sekolah ini
    $sekolah = \App\Models\Sekolah::findOrFail($sekolahId);
    $jenjang = strtoupper($sekolah->jenjang);
    $tingkatAkhir = $this->getTingkatAkhir($sekolahId);

    $sudahAda = Skl::where('siswa_id', $request->siswa_id)
      ->where('sekolah_id', $sekolahId)
      ->exists();

    if ($sudahAda) {
      if ($request->expectsJson()) {
        return response()->json(['message' => 'Siswa ini sudah memiliki data SKL.'], 422);
      }
      return back()->withErrors(['siswa_id' => 'Siswa ini sudah memiliki data SKL.']);
    }

    // ══════════════════════════════════════════════════════════════════════
    // PENGECEKAN VALIDASI MASTER DATA SEBELUM GENERATE
    // ══════════════════════════════════════════════════════════════════════

    // A. Cek Ketersediaan Mapel Mapping
    $mappings = MapelMapping::where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $tingkatAkhir)
      ->where(function ($q) use ($sekolahId) {
        $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
      })
      ->orderBy('urutan')
      ->get();

    if ($mappings->isEmpty()) {
      $pesanError = "Mata pelajaran untuk jenjang {$jenjang} tingkat {$tingkatAkhir} belum dikonfigurasi.";
      if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
      return back()->with('error', $pesanError);
    }

    // B. Cek Ketersediaan Komponen (Jika mode manual)
    $komponens = collect();
    if ($modeInput === 'manual') {
      $komponens = SklKomponen::untuk((int) $semesterId, $jenjang)
        ->with('subKomponen')
        ->get();

      // B1. Apakah komponen sudah dibuat?
      if ($komponens->isEmpty()) {
        $pesanError = "Komponen nilai SKL (mode manual berbobot) belum dikonfigurasi oleh Admin.";
        if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
        return back()->with('error', $pesanError);
      }

      // B2. Apakah total bobot komponen genap 100%?
      $totalBobot = $komponens->sum('bobot');
      if ($totalBobot != 100) {
        $pesanError = "Total bobot komponen SKL saat ini adalah {$totalBobot}%. Bobot harus genap 100% agar sistem dapat membuat data SKL.";
        if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
        return back()->with('error', $pesanError);
      }
    }

    // ══════════════════════════════════════════════════════════════════════
    // PROSES GENERATE DATA
    // ══════════════════════════════════════════════════════════════════════

    DB::transaction(function () use ($request, $semesterId, $sekolahId, $modeInput, $konfigurasi, $mappings, $komponens) {
      $skl = Skl::create([
        'siswa_id'    => $request->siswa_id,
        'sekolah_id'  => $sekolahId,
        'semester_id' => $semesterId,
        'mode_input'  => $modeInput,
        'status'      => 'draft',
      ]);

      $konfigurasi->kunci();

      foreach ($mappings as $mapping) {
        $sklNilai = SklNilai::create([
          'skl_id'           => $skl->id,
          'mapel_mapping_id' => $mapping->id,
          'nilai_akhir'      => null,
        ]);

        if ($modeInput === 'manual') {
          foreach ($komponens as $komponen) {
            $sklNilaiKomponen = SklNilaiKomponen::create([
              'skl_nilai_id' => $sklNilai->id,
              'komponen_id'  => $komponen->id,
              'nilai'        => null,
            ]);

            foreach ($komponen->subKomponen as $sub) {
              SklNilaiSubKomponen::create([
                'skl_nilai_komponen_id' => $sklNilaiKomponen->id,
                'sub_komponen_id'       => $sub->id,
                'nilai'                 => null,
              ]);
            }
          }
        }
      }
    });

    if ($request->expectsJson()) {
      $skl = Skl::where('siswa_id', $request->siswa_id)
        ->where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)
        ->first();
      return response()->json([
        'message'  => 'Data SKL berhasil dibuat.',
        'skl_id'   => $skl->id,
        'edit_url' => route('skl.nilai.edit', $skl),
      ]);
    }

    return redirect()
      ->route('skl.index')
      ->with('success', 'Data SKL berhasil dibuat. Silakan input nilai.');
  }

  // ── Edit Nilai ────────────────────────────────────────────────────────

  public function editNilai(Skl $skl)
  {
    abort_if(
      !in_array($skl->status, ['draft', 'ditolak']),
      403,
      'Nilai tidak dapat diubah pada status ' . $skl->status
    );

    $skl->load(['siswa', 'semester']);
    $nilais = $skl->nilaisLengkap();

    return view('content.skl.show', compact('skl', 'nilais'))
      ->with('viewMode', 'edit');
  }

  public function updateNilai(Request $request, Skl $skl)
  {
    abort_if(
      !in_array($skl->status, ['draft', 'ditolak']),
      403,
      'Nilai tidak dapat diubah pada status ' . $skl->status
    );

    DB::transaction(function () use ($request, $skl) {
      if ($skl->mode_input === 'jadi') {
        $this->simpanNilaiJadi($request, $skl);
      } else {
        $this->simpanNilaiManual($request, $skl);
      }
    });

    return response()->json(['message' => 'Nilai berhasil disimpan.']);
  }

  // ── Input Nomor SKL ───────────────────────────────────────────────────

  public function editNomor(Skl $skl)
  {
    abort_if(!in_array($skl->status, ['draft', 'ditolak', 'disetujui']), 403);
    return view('content.skl.edit', compact('skl'));
  }

  public function updateNomor(Request $request, Skl $skl)
  {
    abort_if(!in_array($skl->status, ['draft', 'ditolak', 'disetujui']), 403);

    $data = $request->validate([
      'nomor_skl'      => 'nullable|string|max:100',
      'tanggal_terbit' => 'nullable|date',
    ]);

    $skl->update($data);

    if ($request->expectsJson()) {
      return response()->json(['message' => 'Nomor SKL berhasil disimpan.']);
    }

    return redirect()
      ->route('skl.show', $skl)
      ->with('success', 'Nomor SKL berhasil diperbarui.');
  }

  // ── Alur Status ───────────────────────────────────────────────────────

  public function ajukan(Skl $skl)
  {
    abort_if(!in_array($skl->status, ['draft', 'ditolak']), 403);

    if (!$skl->sudahLengkap()) {
      return response()->json([
        'message' => 'Semua nilai mapel harus diisi sebelum mengajukan.',
        'errors'  => ['nilai' => 'Semua nilai mapel harus diisi sebelum mengajukan.'],
      ], 422);
    }

    $skl->update(['status' => 'pengajuan']);

    return response()->json(['message' => 'SKL berhasil diajukan.']);
  }

  public function setujui(Skl $skl)
  {
    abort_if($skl->status !== 'pengajuan', 403);

    $skl->update(['status' => 'disetujui', 'generated_at' => now()]);

    return response()->json(['message' => 'SKL berhasil disetujui.']);
  }

  public function tolak(Request $request, Skl $skl)
  {
    abort_if($skl->status !== 'pengajuan', 403);

    $request->validate(['catatan' => 'required|string|max:500']);

    $skl->update(['status' => 'ditolak', 'catatan' => $request->catatan]);

    return response()->json(['message' => 'SKL dikembalikan untuk diperbaiki.']);
  }

  public function destroy(Skl $skl)
  {
    abort_if($skl->status === 'disetujui', 403, 'SKL yang sudah disetujui tidak dapat dihapus.');

    $sekolahId = $skl->sekolah_id;
    $semesterId = $skl->semester_id;

    // Ganti delete() menjadi forceDelete() agar terhapus permanen dari MySQL
    $skl->forceDelete();

    // CEK MANUAL: Apakah masih ada SKL lain?
    $masihAda = Skl::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->exists();

    // Jika sudah tidak ada, buka kunci
    if (!$masihAda) {
      SklKonfigurasi::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)
        ->update(['is_terkunci' => false]);
    }

    if (request()->expectsJson()) {
      return response()->json(['message' => 'Data SKL berhasil dihapus.']);
    }

    return redirect()->route('skl.index')->with('success', 'Data SKL dihapus.');
  }

  public function bulkAjukan(Request $request)
  {
    $request->validate(['ids' => 'required|array']);

    // Hanya ubah yang statusnya masih draft atau ditolak
    $affected = Skl::whereIn('id', $request->ids)
      ->whereIn('status', ['draft', 'ditolak'])
      ->update(['status' => 'pengajuan']);

    return response()->json(['message' => "$affected SKL berhasil diajukan."]);
  }

  public function bulkSetujui(Request $request)
  {
    $request->validate(['ids' => 'required|array']);

    // Hanya ubah yang statusnya pengajuan
    $affected = Skl::whereIn('id', $request->ids)
      ->where('status', 'pengajuan')
      ->update(['status' => 'disetujui']);

    return response()->json(['message' => "$affected SKL berhasil disetujui."]);
  }

  public function bulkBatalSetujui(Request $request)
  {
    $request->validate(['ids' => 'required|array']);

    // Hanya memproses SKL yang saat ini berstatus 'disetujui'
    // Status dikembalikan ke 'pengajuan' agar bisa diperiksa/diedit kembali
    $affected = Skl::whereIn('id', $request->ids)
      ->where('status', 'disetujui')
      ->update([
        'status' => 'pengajuan',
        'catatan' => 'batal disetujui'
      ]);

    return response()->json([
      'status' => 'success',
      'message' => "$affected SKL berhasil dibatalkan persetujuannya."
    ]);
  }

  public function bulkTolak(Request $request)
  {
    $request->validate([
      'ids' => 'required|array',
      'catatan' => 'required|string'
    ]);

    // Update status menjadi ditolak dan isikan catatan
    $affected = Skl::whereIn('id', $request->ids)
      ->where('status', 'pengajuan')
      ->update([
        'status' => 'ditolak',
        'catatan' => $request->catatan
      ]);

    return response()->json(['message' => "$affected SKL berhasil ditolak."]);
  }

  public function bulkHapus(Request $request)
  {
    $request->validate(['ids' => 'required|array']);

    // Opsional: cegah penghapusan data yang sudah disetujui
    $affected = Skl::whereIn('id', $request->ids)
      ->where('status', '!=', 'disetujui')
      ->delete();

    return response()->json(['message' => "$affected data SKL berhasil dihapus permanen."]);
  }

  // ── Auto-generate nomor SKL ───────────────────────────────────────────

  public function generateNomor(Skl $skl)
  {
    $sekolahId  = $this->getSekolahId();
    $konfigurasi = SklKonfigurasi::firstOrDefault($sekolahId, (int) session('semester_id'));

    // Format: {prefix}/{urutan}/{bulan}/{tahun}
    // Contoh: SKL/001/VI/2025
    $prefix  = $konfigurasi->prefix_nomor ?? 'SKL';
    $tahun   = now()->year;
    $bulan   = strtoupper(\Carbon\Carbon::now()->isoFormat('MMMM'));

    $urutan  = Skl::where('sekolah_id', $sekolahId)
      ->whereYear('created_at', $tahun)
      ->whereNotNull('nomor_skl')
      ->count() + 1;

    $nomor = sprintf('%s/%03d/%s/%d', $prefix, $urutan, $bulan, $tahun);

    return response()->json(['nomor' => $nomor]);
  }

  // ── Sinkronisasi Struktur & Perhitungan Ulang Nilai SKL ─────────────────

  public function sync(Request $request)
  {
    $semesterId  = session('semester_id');
    $sekolahId   = $this->getSekolahId();

    $konfigurasi = SklKonfigurasi::untukSekolah($sekolahId, (int) $semesterId);
    if (!$konfigurasi) {
      return response()->json(['message' => 'Konfigurasi SKL tidak ditemukan.'], 422);
    }

    $modeInput   = $konfigurasi->mode_input;
    $sekolah     = \App\Models\Sekolah::findOrFail($sekolahId);
    $jenjang     = strtoupper($sekolah->jenjang);
    $tingkatAkhir = $this->getTingkatAkhir($sekolahId);

    // 1. Ambil semua SKL sekolah ini di semester ini yang BELUM disetujui
    $skls = Skl::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->where('status', '!=', 'disetujui')
      ->get();

    if ($skls->isEmpty()) {
      return response()->json(['message' => 'Tidak ada data SKL aktif yang perlu disinkronkan.']);
    }

    // 2. Ambil Master Mapping Mapel Terbaru
    $mappings = MapelMapping::where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $tingkatAkhir)
      ->where(function ($q) use ($sekolahId) {
        $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
      })
      ->get();

    // 3. Ambil Master Komponen Terbaru
    $komponens = collect();
    if ($modeInput === 'manual') {
      $komponens = SklKomponen::untuk((int) $semesterId, $jenjang)->with('subKomponen')->get();

      // Validasi total bobot wajib 100%
      if ($komponens->sum('bobot') != 100) {
        return response()->json([
          'message' => 'Gagal sinkronisasi. Total bobot komponen saat ini ' . $komponens->sum('bobot') . '%. Harap perbaiki bobot hingga genap 100% terlebih dahulu.'
        ], 422);
      }
    }

    // 4. Proses Eksekusi Sinkronisasi secara Massal dalam Database Transaction
    DB::transaction(function () use ($skls, $mappings, $komponens, $modeInput) {
      foreach ($skls as $skl) {

        // --- A. SINKRONISASI MAPEL MAPPING (TAMBAH / HAPUS MAPEL) ---
        $existingMappingIds = SklNilai::where('skl_id', $skl->id)->pluck('mapel_mapping_id')->toArray();
        $currentMappingIds = $mappings->pluck('id')->toArray();

        // Tambah jika ada mapel mapping baru bertambah
        foreach ($mappings as $mapping) {
          if (!in_array($mapping->id, $existingMappingIds)) {
            $sklNilai = SklNilai::create([
              'skl_id'           => $skl->id,
              'mapel_mapping_id' => $mapping->id,
              'nilai_akhir'      => null,
            ]);

            // Jika mode manual, buat juga struktur komponen kosongan di bawahnya
            if ($modeInput === 'manual') {
              foreach ($komponens as $komponen) {
                $sklNilaiKomponen = SklNilaiKomponen::create([
                  'skl_nilai_id' => $sklNilai->id,
                  'komponen_id'  => $komponen->id,
                  'nilai'        => null,
                ]);

                foreach ($komponen->subKomponen as $sub) {
                  SklNilaiSubKomponen::create([
                    'skl_nilai_komponen_id' => $sklNilaiKomponen->id,
                    'sub_komponen_id'       => $sub->id,
                    'nilai'                 => null,
                  ]);
                }
              }
            }
          }
        }

        // Hapus data SklNilai jika MapelMapping aslinya sudah dihapus dari master data
        SklNilai::where('skl_id', $skl->id)
          ->whereNotIn('mapel_mapping_id', $currentMappingIds)
          ->delete();


        // --- B. SINKRONISASI STRUKTUR KOMPONEN & HITUNG ULANG BOBOT BARU ---
        if ($modeInput === 'manual') {
          // Load ulang data nilai setelah update struktur mapel tadi
          $sklNilais = SklNilai::where('skl_id', $skl->id)->get();

          foreach ($sklNilais as $sklNilai) {
            foreach ($komponens as $komponen) {

              // Gunakan firstOrCreate untuk mengamankan jika ada komponen master yang baru bertambah
              $kn = SklNilaiKomponen::firstOrCreate([
                'skl_nilai_id' => $sklNilai->id,
                'komponen_id'  => $komponen->id,
              ]);

              foreach ($komponen->subKomponen as $sub) {
                SklNilaiSubKomponen::firstOrCreate([
                  'skl_nilai_komponen_id' => $kn->id,
                  'sub_komponen_id'       => $sub->id,
                ]);
              }
            }

            // Jalankan perhitungan ulang secara sekuensial memanfaatkan method pembantu yang sudah Anda buat
            foreach ($sklNilai->komponens as $kn) {
              if ($kn->komponen->subKomponen->isNotEmpty()) {
                $kn->refresh()->hitungDariSub();
              }
            }

            // Trigger perhitungan nilai akhir berdasarkan bobot terbaru yang tersimpan di master komponen
            $sklNilai->refresh()->hitungNilaiAkhir();
          }
        } else {
          // Jika mode input "jadi", kita cukup memicu ulang konversi angka ke huruf jika barangkali diperlukan
          foreach ($skl->nilais as $sklNilai) {
            if ($sklNilai->nilai_akhir !== null) {
              $sklNilai->update([
                'nilai_huruf' => SklNilai::angkaKeHuruf((float) $sklNilai->nilai_akhir)
              ]);
            }
          }
        }
      }
    });

    return response()->json([
      'message' => 'Sinkronisasi berhasil! Struktur mata pelajaran telah disesuaikan dan seluruh nilai siswa berhasil dihitung ulang berdasarkan bobot terbaru.'
    ]);
  }

  // ── Private Helpers ───────────────────────────────────────────────────

  private function simpanNilaiJadi(Request $request, Skl $skl): void
  {
    $rules = [];
    foreach ($skl->nilais as $sklNilai) {
      $rules["nilai.{$sklNilai->id}"] = 'nullable|numeric|min:0|max:100';
    }
    $request->validate($rules);

    foreach ($skl->nilais as $sklNilai) {
      $nilaiInput = $request->input("nilai.{$sklNilai->id}");
      $sklNilai->update([
        'nilai_akhir' => $nilaiInput,
        'nilai_huruf' => $nilaiInput !== null
          ? SklNilai::angkaKeHuruf((float) $nilaiInput)
          : null,
      ]);
    }
  }

  private function simpanNilaiManual(Request $request, Skl $skl): void
  {
    $rules = [];
    foreach ($skl->nilais as $sklNilai) {
      foreach ($sklNilai->komponens as $kn) {
        if ($kn->komponen->subKomponen->isNotEmpty()) {
          foreach ($kn->nilaiSubKomponen as $nsk) {
            $rules["sub.{$sklNilai->id}.{$kn->komponen_id}.{$nsk->sub_komponen_id}"]
              = 'nullable|numeric|min:0|max:100';
          }
        } else {
          $rules["nilai.{$sklNilai->id}.{$kn->komponen_id}"]
            = 'nullable|numeric|min:0|max:100';
        }
      }
    }
    $request->validate($rules);

    foreach ($skl->nilais as $sklNilai) {
      foreach ($sklNilai->komponens as $kn) {
        if ($kn->komponen->subKomponen->isNotEmpty()) {
          foreach ($kn->nilaiSubKomponen as $nsk) {
            $nsk->update([
              'nilai' => $request->input(
                "sub.{$sklNilai->id}.{$kn->komponen_id}.{$nsk->sub_komponen_id}"
              ),
            ]);
          }
          $kn->refresh()->hitungDariSub();
        } else {
          $kn->update([
            'nilai' => $request->input("nilai.{$sklNilai->id}.{$kn->komponen_id}"),
          ]);
        }
      }
      $sklNilai->refresh()->hitungNilaiAkhir();
    }
  }

  private function getMapels(string $jenjang, int $tingkat, int $semesterId, int $sekolahId)
  {
    return MapelMapping::with(['masterMapel.kelompok'])
      ->where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $tingkat)
      ->where(function ($q) use ($sekolahId) {
        // pokok (berlaku semua sekolah) atau tambahan milik sekolah ini
        $q->whereNull('sekolah_id')
          ->orWhere('sekolah_id', $sekolahId);
      })
      ->whereHas('masterMapel', fn($q) => $q->where('is_aktif', true))
      ->get()
      ->sortBy(fn($m) => [
        $m->masterMapel->kelompok->urutan ?? 999,
        $m->urutan ?? 999,
      ]);
  }

  // =========================================================================
  // GENERATE TEMPLATE EXCEL (MULTI-MODE)
  // =========================================================================
  public function downloadTemplate(Request $request)
  {
    $user = Auth::user();

    if (!$user->hasRole('Operator Sekolah')) {
      abort(403);
    }

    // 1. Ambil Semester & Data Sekolah PERSIS seperti menu SKHU Anda
    $semester = Semester::where('id', session('semester_id'))->first()
      ?? Semester::where('is_active', 1)->first();

    $sekolahId = $user->ptk->sekolah_id;

    if (!$sekolahId) {
      return response()->json(['message' => 'Sekolah tidak ditemukan.'], 422);
    }

    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $jenjang = strtoupper($sekolah->jenjang);

    $tingkatAkhir = $this->tingkatAkhir[$jenjang] ?? 12;

    // 2. Ambil Mapel menggunakan fungsi bawaan controller Anda
    $mapels = $this->getMapels($jenjang, $tingkatAkhir, $semester->id, $sekolah->id);

    if ($mapels->isEmpty()) {
      return response()->json([
        'message' => "Belum ada mapel untuk jenjang {$jenjang} semester {$semester->kode_semester}.",
      ], 422);
    }

    // 3. Ambil data siswa menggunakan RombelAnggota (Persis seperti menu SKHU)
    $siswaList = RombelAnggota::with(['siswa', 'rombel'])
      ->whereHas(
        'rombel',
        fn($q) => $q
          ->where('sekolah_id', $sekolah->id)
          ->where('semester_id', $semester->id)
          ->where('tingkat', $tingkatAkhir)
      )
      ->whereHas('siswa', fn($q) => $q->whereHas('registrasiTerbaru', fn($qr) => $qr->whereIn('status', ['aktif', 'lulus'])))
      ->get()
      ->sortBy('siswa.nama_siswa');

    // 4. Cek Konfigurasi SKL Sekolah Anda
    $konfigurasi = SklKonfigurasi::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id)
      ->first();

    if (!$konfigurasi) {
      return back()->with('error', 'Konfigurasi SKL belum diatur.');
    }

    // 5. Ambil Data SKL yang sudah ada untuk mapping nomor & status lock
    $skls = Skl::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id)
      ->get()
      ->keyBy('siswa_id');

    $spreadsheet = new Spreadsheet();

    // 6. Percabangan Sheet berdasarkan Mode Input SKL
    if ($konfigurasi->mode_input === 'jadi') {
      // MODE JADI: 1 Sheet Saja
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setTitle('Nilai Akhir');
      $this->buildSheetModeJadi($sheet, $mapels, $siswaList, $skls);
    } else {
      // MODE MANUAL: Multi-Sheet berdasarkan Master Komponen Nilai SKL
      $komponens = SklKomponen::where('semester_id', $semester->id)
        ->where('jenjang', $jenjang)
        ->with('subKomponen')
        ->get();

      if ($komponens->isEmpty()) {
        return back()->with('error', 'Komponen master nilai belum dikonfigurasi.');
      }

      $sheetIndex = 0;
      foreach ($komponens as $komponen) {
        if ($sheetIndex === 0) {
          $sheet = $spreadsheet->getActiveSheet();
        } else {
          $sheet = $spreadsheet->createSheet();
        }

        // 1. Bersihkan nama komponen dari karakter terlarang Excel: \ / ? * : [ ]
        $namaClean = str_replace(['\\', '/', '?', '*', ':', '[', ']'], ' ', $komponen->label ?? '');

        // 2. Potong maksimal 31 karakter & bersihkan spasi di ujungnya
        $title = trim(substr($namaClean, 0, 31));

        // 3. Jaga-jaga jika nama komponen di DB kosong/berisi karakter aneh, beri nama default
        if (empty($title)) {
          $title = 'Komponen ' . ($sheetIndex + 1);
        }

        // Set nama sheet yang sudah aman
        $sheet->setTitle($title);

        $this->buildSheetModeManual($sheet, $komponen, $mapels, $siswaList, $skls);
        $sheetIndex++;
      }
      $spreadsheet->setActiveSheetIndex(0);
    }

    $namaFile = "Template-Nilai-SKL-{$jenjang}-" . str_replace('/', '-', $semester->tahun_pelajaran) . ".xlsx";
    $writer   = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    // Kirim response stream download
    return response()->streamDownload(function () use ($writer) {
      // Bersihkan output buffer terlebih dahulu agar file binary tidak korup/invalid
      if (ob_get_contents()) {
        ob_end_clean();
      }
      $writer->save('php://output');
    }, $namaFile, [
      'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Cache-Control' => 'max-age=0',
    ]);
  }

  // Helper: Build Sheet Mode Jadi (Tanpa Sub-Komponen)
  private function buildSheetModeJadi($sheet, $mapels, $siswaList, $skls)
  {
    $sheet->setCellValue('A1', 'id_siswa');
    $sheet->setCellValue('B1', 'Nama Siswa');
    $sheet->setCellValue('C1', 'NISN');
    $sheet->setCellValue('D1', 'Status');
    $sheet->setCellValue('E1', 'nomor_skl');

    $col = 6; // Mulai dari Kolom F
    foreach ($mapels as $mapel) {
      // Baris 1: Tetap diisi Kunci ID untuk kebutuhan Engine Import
      $sheet->setCellValue([$col, 1], "mapel_{$mapel->id}");

      // Baris 2 & 3: Merge vertikal, diisi Nama Singkat Mapel
      $sheet->mergeCells([$col, 2, $col, 3]);
      $sheet->setCellValue([$col, 2], $mapel->masterMapel->nama_singkat ?? $mapel->masterMapel->nama);

      // Tambahkan Hover Comment Nama Panjang Mapel
      $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
      $sheet->getComment("{$colStr}2")->getText()->createTextRun($mapel->masterMapel->nama);

      $col++;
    }

    $this->fillDataSiswa($sheet, $siswaList, $skls, $col - 1);
  }

  // Helper: Build Sheet Mode Manual (Hierarki Dibalik Sesuai Request)
  private function buildSheetModeManual($sheet, $komponen, $mapels, $siswaList, $skls)
  {
    $sheet->setCellValue('A1', 'id_siswa');
    $sheet->setCellValue('B1', 'Nama Siswa');
    $sheet->setCellValue('C1', 'NISN');
    $sheet->setCellValue('D1', 'Status');
    $sheet->setCellValue('E1', 'nomor_skl');

    $col = 6;
    if ($komponen->subKomponen->isNotEmpty()) {
      // LOOP 1: Sub-Komponen diletakkan di atas (Baris 2)
      foreach ($komponen->subKomponen as $sub) {
        $startCol = $col;

        // LOOP 2: Mapel diletakkan di bawahnya (Baris 3)
        foreach ($mapels as $mapel) {
          // Baris 1: Simpan Hidden Key Komplit untuk Engine Import
          $sheet->setCellValue([$col, 1], "mapel_{$mapel->id}_komp_{$komponen->id}_sub_{$sub->id}");

          // Baris 3: Nama Singkat Mapel Master
          $sheet->setCellValue([$col, 3], $mapel->masterMapel->nama_singkat ?? $mapel->masterMapel->nama);

          // Beri Hover Comment Nama Lengkap Mapel pada Baris 3
          $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
          $sheet->getComment("{$colStr}3")->getText()->createTextRun($mapel->masterMapel->nama);

          $col++;
        }

        // Merge Label Sub-Komponen di Baris 2 sepanjang mapel yang ada
        $sheet->mergeCells([$startCol, 2, $col - 1, 2]);
        $sheet->setCellValue([$startCol, 2], $sub->label);
        $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($startCol);
        $sheet->getComment("{$colStr}2")->getText()->createTextRun($sub->keterangan);
      }
    } else {
      // JIKA TIDAK ADA SUB-KOMPONEN: Mapel langsung di-merge Baris 2 & 3
      foreach ($mapels as $mapel) {
        $sheet->setCellValue([$col, 1], "mapel_{$mapel->id}_komp_{$komponen->id}");
        $sheet->mergeCells([$col, 2, $col, 3]);
        $sheet->setCellValue([$col, 2], $mapel->masterMapel->nama_singkat ?? $mapel->masterMapel->nama);

        $colStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
        $sheet->getComment("{$colStr}2")->getText()->createTextRun($mapel->masterMapel->nama);
        $col++;
      }
    }

    $this->fillDataSiswa($sheet, $siswaList, $skls, $col - 1);
  }

  // Helper: Mewarnai, Mengunci Cell, Paksa NISN Text & Auto-Size Column Width
  private function fillDataSiswa($sheet, $siswaList, $skls, $maxCol)
  {
    $maxColStr = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($maxCol);

    // 1. Pengaturan Tinggi Baris (Biar longgar & estetik)
    $sheet->getRowDimension(1)->setVisible(false); // Brankas Kunci tetap sembunyi
    $sheet->getColumnDimension('A')->setVisible(false); // ID Siswa tetap sembunyi
    $sheet->getRowDimension(2)->setRowHeight(26);
    $sheet->getRowDimension(3)->setRowHeight(26);

    // 2. Set Judul Utama Kolom Profil Siswa
    $sheet->setCellValue('B2', 'Nama Siswa')->mergeCells('B2:B3');
    $sheet->setCellValue('C2', 'NISN')->mergeCells('C2:C3');
    $sheet->setCellValue('D2', 'Status SKL')->mergeCells('D2:D3');
    $sheet->setCellValue('E2', 'Nomor SKL')->mergeCells('E2:E3');

    // 3. Styling Header Utama (Baris 2 & 3)
    $styleHeader = [
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
      'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4A5568']], // Slate Gray Modern
      'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        'wrapText' => true
      ],
      'borders' => [
        'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E0']]
      ]
    ];
    $sheet->getStyle("B2:{$maxColStr}3")->applyFromArray($styleHeader);

    // 4. Looping Isi Data Siswa
    $row = 4;
    foreach ($siswaList as $anggota) {
      $siswa = $anggota->siswa;
      $skl = $skls->get($siswa->id);
      $status = $skl ? ucfirst($skl->status) : 'Belum';

      $sheet->getRowDimension($row)->setRowHeight(20); // Tinggi baris data siswa

      $sheet->setCellValue("A{$row}", $siswa->id);
      $sheet->setCellValue("B{$row}", $siswa->nama_siswa);

      // --- MANDATORI REQUEST: Paksa NISN Menjadi Text Jamak (Mencegah Angka 0 di depan hilang) ---
      $sheet->setCellValueExplicit("C{$row}", $siswa->nisn ?? '', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
      $sheet->getStyle("C{$row}")->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

      $sheet->setCellValue("D{$row}", $status);
      $sheet->setCellValue("E{$row}", $skl?->nomor_skl ?? '');

      // Styling standar baris siswa (Border tipis & vertical alignment middle)
      $styleRowDefault = [
        'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
        'alignment' => ['vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER]
      ];
      $sheet->getStyle("B{$row}:{$maxColStr}{$row}")->applyFromArray($styleRowDefault);

      // Perataan tengah untuk Kolom NISN, Status, dan semua kolom Nilai ke kanan
      $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
      $sheet->getStyle("D{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
      if ($maxCol >= 6) {
        $sheet->getStyle("F{$row}:{$maxColStr}{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
      }

      // Jika statusnya DISETUJUI, warnai merah muda lembut & kunci barisnya
      if ($skl && $skl->status === 'disetujui') {
        $sheet->getStyle("B{$row}:{$maxColStr}{$row}")->applyFromArray([
          'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF5F5']], // Soft Red
          'font' => ['color' => ['rgb' => 'C53030']]
        ]);
        $sheet->setCellValue("B{$row}", $siswa->nama_siswa . " (Terkunci)");
      }
      $row++;
    }

    // 5. Freeze Pane (Agar kolom nama siswa tidak ikut tergeser saat scroll ke kanan)
    $sheet->freezePane('F4');

    // 6. Proteksi Lembar Kerja (Password: 123)
    $prot = $sheet->getProtection();
    $prot->setSheet(true);
    $prot->setPassword('123');

    // Buka proteksi khusus untuk area input nilai (Kolom E ke kanan, baris 4 sampai bawah)
    $sheet->getStyle("E4:{$maxColStr}" . ($row - 1))->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);

    // Kunci mati kembali khusus baris siswa yang sudah berstatus 'disetujui'
    $rowLagi = 4;
    foreach ($siswaList as $anggota) {
      $siswa = $anggota->siswa;
      $skl = $skls->get($siswa->id);
      if ($skl && $skl->status === 'disetujui') {
        $sheet->getStyle("E{$rowLagi}:{$maxColStr}{$rowLagi}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_PROTECTED);
      }
      $rowLagi++;
    }

    // 7. AUTO-FIT COLUMN WIDTH (Lebar kolom otomatis menyesuaikan panjang karakter teks)
    foreach (range('B', $maxColStr) as $columnID) {
      $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }
  }

  // =========================================================================
  // EKSEKUSI IMPORT
  // =========================================================================
  public function importNilai(Request $request)
  {
    $request->validate([
      'file_excel' => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    $sekolahId  = $this->getSekolahId();
    $semesterId = session('semester_id');

    $konfigurasi = SklKonfigurasi::untukSekolah($sekolahId, (int) $semesterId);

    if (!$konfigurasi) {
      $pesanError = "Konfigurasi SKL untuk semester ini belum diatur. Silakan atur konfigurasi terlebih dahulu.";
      if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
      return back()->with('error', $pesanError);
    }

    $modeInput   = $konfigurasi->mode_input;

    // Dapatkan Jenjang dan Tingkat Akhir sekolah ini
    $sekolah = \App\Models\Sekolah::findOrFail($sekolahId);
    $jenjang = strtoupper($sekolah->jenjang);
    $tingkatAkhir = $this->getTingkatAkhir($sekolahId);

    // ══════════════════════════════════════════════════════════════════════
    // PENGECEKAN VALIDASI MASTER DATA SEBELUM GENERATE
    // ══════════════════════════════════════════════════════════════════════

    // A. Cek Ketersediaan Mapel Mapping
    $mappings = MapelMapping::where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $tingkatAkhir)
      ->where(function ($q) use ($sekolahId) {
        $q->whereNull('sekolah_id')->orWhere('sekolah_id', $sekolahId);
      })
      ->orderBy('urutan')
      ->get();

    if ($mappings->isEmpty()) {
      $pesanError = "Mata pelajaran untuk jenjang {$jenjang} tingkat {$tingkatAkhir} belum dikonfigurasi.";
      if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
      return back()->with('error', $pesanError);
    }

    // B. Cek Ketersediaan Komponen (Jika mode manual)
    $komponens = collect();
    if ($modeInput === 'manual') {
      $komponens = SklKomponen::untuk((int) $semesterId, $jenjang)
        ->with('subKomponen')
        ->get();

      // B1. Apakah komponen sudah dibuat?
      if ($komponens->isEmpty()) {
        $pesanError = "Komponen nilai SKL (mode manual berbobot) belum dikonfigurasi oleh Admin.";
        if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
        return back()->with('error', $pesanError);
      }

      // B2. Apakah total bobot komponen genap 100%?
      $totalBobot = $komponens->sum('bobot');
      if ($totalBobot != 100) {
        $pesanError = "Total bobot komponen SKL saat ini adalah {$totalBobot}%. Bobot harus genap 100% agar sistem dapat membuat data SKL.";
        if ($request->expectsJson()) return response()->json(['message' => $pesanError], 422);
        return back()->with('error', $pesanError);
      }
    }

    try {
      $import = new SklImport($sekolahId, $semesterId, $konfigurasi->mode_input, $mappings, $komponens);
      Excel::import($import, $request->file('file_excel'));

      return response()->json([
        'status'     => 'success',
        'message'    => "Import selesai. {$import->berhasil} siswa diperbarui/dibuat. {$import->dilewati} siswa dilewati (sudah disetujui).",
        'berhasil'   => $import->berhasil,
        'dilewati'   => $import->dilewati,
      ]);
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage()
      ], 500);
    }
  }

  // 1. Preview PDF langsung di Browser (Termasuk QR Code)
  public function previewPdf(int $sklId)
  {
    $user    = Auth::user();
    $sekolah = Sekolah::findOrFail($user->ptk->sekolah_id);

    $skl = Skl::with([
      'siswa',
      'sekolah.detailPerSemester.village'
    ])
      ->where('sekolah_id', $sekolah->id)
      ->findOrFail($sklId);

    // Generate URL untuk QR Code Validator
    $urlCekSkl = route('api.skl', ['nisn' => $skl->siswa->nisn]); // Sesuaikan rute API validasi Anda

    $options = new QROptions([
      'version'          => 5,
      'outputType'       => 'svg',
      'eccLevel'         => 1,
      'scale'            => 10,
      'imageBase64'      => true,
      'drawLightModules' => true,
      'svgConnectPaths'  => true,
    ]);

    $qrCodeBase64 = (new QRCode($options))->render($urlCekSkl);

    $html = view('templates.skl-template.skl-2425-sd', [
      'skl'   => $skl,
      'qrCode' => $qrCodeBase64,
    ])->render();

    $pdf = Pdf::loadHTML($html)
      ->setPaper('a4', 'portrait')
      ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => true,
        'defaultFont'          => 'serif',
        'dpi'                  => 150,
      ]);

    $namaSiswa = str_replace(' ', '_', strtoupper($skl->siswa->nama_siswa));
    $namaFile  = "PREVIEW_SKL_{$namaSiswa}.pdf";

    return $pdf->stream($namaFile, ['attachment' => false]);
  }

  // 2. Download Langsung PDF Tunggal per Siswa
  public function generatePdf(int $sklId)
  {
    $user    = Auth::user();
    $sekolah = Sekolah::findOrFail($user->ptk->sekolah_id);

    $skl = Skl::with([
      'siswa',
      'sekolah.detailPerSemester.village'
    ])
      ->where('sekolah_id', $sekolah->id)
      ->findOrFail($sklId);

    // Update timestamp generated_at jika kolomnya tersedia di database Anda
    if (Schema::hasColumn('skls', 'generated_at')) {
      $skl->update(['generated_at' => now()]);
    }

    // Tetap buat QR Code agar hasil download resmi memiliki QR Code
    $urlCekSkl = route('api.skl', ['nisn' => $skl->siswa->nisn]);
    $options   = new QROptions(['version' => 5, 'outputType' => 'svg', 'eccLevel' => 1, 'scale' => 10, 'imageBase64' => true]);
    $qrCodeBase64 = (new QRCode($options))->render($urlCekSkl);

    $html = view('templates.skl-template.skl-2425-sd', [
      'skl'    => $skl,
      'qrCode' => $qrCodeBase64,
    ])->render();

    $pdf = Pdf::loadHTML($html)
      ->setPaper('a4', 'portrait')
      ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => true,
        'defaultFont'          => 'serif',
        'dpi'                  => 150,
      ]);

    $namaSiswa = str_replace(' ', '_', strtoupper($skl->siswa->nama_siswa));
    $namaFile  = "SKL_{$namaSiswa}_{$skl->tahun_pelajaran}.pdf";

    return $pdf->download($namaFile);
  }

  // 3. Download Masal Terpilih via Checkbox menjadi file ZIP
  public function generateBulk(Request $request)
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::findOrFail($user->ptk->sekolah_id);
    $semester = Semester::findOrFail(session('semester_id'));

    $query = Skl::with([
      'siswa',
      'sekolah.detailPerSemester.village'
    ])
      ->where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id);

    // Filter berdasarkan checkbox ID jika parameter dikirimkan
    if ($request->has('ids') && !empty($request->ids)) {
      $idsArray = explode(',', $request->ids);
      $query->whereIn('id', $idsArray);
    }

    $skls = $query->get();

    if ($skls->isEmpty()) {
      return response()->json(['message' => 'Belum ada data SKL yang bisa didownload.'], 404);
    }

    $zipNama = "BULK_SKL_" . date('Ymd_His') . '.zip';
    $zipPath = storage_path("app/temp/{$zipNama}");

    if (!file_exists(storage_path('app/temp'))) {
      mkdir(storage_path('app/temp'), 0755, true);
    }

    $zip = new ZipArchive();
    $zip->open($zipPath, ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($skls as $skl) {
      if (\Schema::hasColumn('skls', 'generated_at')) {
        $skl->update(['generated_at' => now()]);
      }

      $urlCekSkl = route('api.skl', ['nisn' => $skl->siswa->nisn]);
      $options   = new QROptions(['version' => 5, 'outputType' => 'svg', 'eccLevel' => 1, 'scale' => 10, 'imageBase64' => true]);
      $qrCodeBase64 = (new QRCode($options))->render($urlCekSkl);

      $html = view('templates.skl-template.skl-2425-sd', [
        'skl'    => $skl,
        'qrCode' => $qrCodeBase64,
      ])->render();

      $pdf = Pdf::loadHTML($html)
        ->setPaper('a4', 'portrait')
        ->setOptions([
          'isHtml5ParserEnabled' => true,
          'isRemoteEnabled'      => true,
          'defaultFont'          => 'serif',
          'dpi'                  => 150,
        ]);

      $namaSiswa = str_replace(' ', '_', strtoupper($skl->siswa->nama_siswa));
      $zip->addFromString("SKL_{$namaSiswa}.pdf", $pdf->output());
    }

    $zip->close();

    return response()->download($zipPath, $zipNama)->deleteFileAfterSend(true);
  }
}
