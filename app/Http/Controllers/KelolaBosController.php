<?php

namespace App\Http\Controllers;

use App\Models\BosAnggaran;
use App\Models\BosKasUmum;
use App\Models\BosRapbs;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefRekening;
use App\Models\BosSinkronLog;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola BOS (fitur: kelola-bos) — Fase 1: tampilan read-only
 * cermin data ARKAS. Dua halaman: Kertas Kerja (bos_rapbs)
 * dan BKU (bos_kas_umum). Data TIDAK diubah dari sini; ia
 * datang dari agen sinkron di laptop ARKAS.
 *
 * Operator Sekolah & Kepala Sekolah melihat sekolahnya;
 * Admin memantau lewat pemilih sekolah (pola Surat Keluar).
 */
class KelolaBosController extends Controller
{
  /** Konteks sekolah aktif (pola yang sama dengan Surat Keluar). */
  private function sekolahKonteksIdNullable(): ?int
  {
    $user = Auth::user();

    if ($user->hasRole('Admin')) {
      if (request()->filled('sekolah_id')) {
        session(['admin_sekolah_id' => request()->integer('sekolah_id')]);
      }

      $sekolahId = session('admin_sekolah_id') ?? $user->ptk?->sekolah_id;

      if (!$sekolahId && Sekolah::count() === 1) {
        $sekolahId = Sekolah::value('id');
      }

      return $sekolahId ? (int) $sekolahId : null;
    }

    return $user->ptk?->sekolah_id ? (int) $user->ptk->sekolah_id : null;
  }

  private function konteksUmum(Request $request): array
  {
    $user = Auth::user();
    $sekolahId = $this->sekolahKonteksIdNullable();

    return [
      'sekolahId' => $sekolahId,
      'daftarSekolah' => $user->hasRole('Admin')
        ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
        : collect(),
      'sekolah' => $sekolahId ? Sekolah::with('detailTerbaru')->find($sekolahId) : null,
      'sinkronTerakhir' => $sekolahId ? BosSinkronLog::terakhirUntuk($sekolahId) : null,
    ];
  }

  /** Arah urutan yang aman dari request. */
  private function arah(Request $request): string
  {
    return strtolower((string) $request->input('dir')) === 'asc' ? 'asc' : 'desc';
  }

  // ── Kertas Kerja ─────────────────────────────────────────

  public function kertasKerja(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.kertas-kerja', $konteks + [
        'baris' => null,
        'totalJumlah' => 0,
        'ringkasan' => collect(),
        'daftarTahun' => [],
        'daftarSumberDana' => collect(),
        'daftarKegiatan' => collect(),
        'urut' => 'kegiatan',
        'arah' => 'asc',
      ]);
    }

    $dasar = fn() => BosRapbs::query()
      ->from('bos_rapbs')
      ->join('bos_anggaran', function ($j) {
        $j->on('bos_anggaran.id_arkas', '=', 'bos_rapbs.id_anggaran_arkas')
          ->on('bos_anggaran.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoin('bos_ref_kode', function ($j) {
        $j->on('bos_ref_kode.id_arkas', '=', 'bos_rapbs.id_ref_kode')
          ->on('bos_ref_kode.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoin('bos_ref_rekening', function ($j) {
        $j->on('bos_ref_rekening.kode_rekening', '=', 'bos_rapbs.kode_rekening')
          ->on('bos_ref_rekening.sekolah_id', '=', 'bos_rapbs.sekolah_id')
          ->on('bos_ref_rekening.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->where('bos_rapbs.sekolah_id', $sekolahId)
      ->where('bos_rapbs.soft_delete', false);

    $saring = function ($q) use ($request) {
      $q->when($request->filled('tahun'), fn($x) => $x->where('bos_anggaran.tahun_anggaran', $request->integer('tahun')))
        ->when($request->filled('sumber_dana'), fn($x) => $x->where('bos_anggaran.id_ref_sumber_dana', $request->integer('sumber_dana')))
        ->when($request->filled('kegiatan'), fn($x) => $x->where('bos_rapbs.id_ref_kode', $request->input('kegiatan')))
        ->when($request->filled('cari'), function ($x) use ($request) {
          $cari = '%' . $request->input('cari') . '%';
          $x->where(function ($y) use ($cari) {
            $y->where('bos_rapbs.uraian', 'like', $cari)
              ->orWhere('bos_rapbs.kode_rekening', 'like', $cari)
              ->orWhere('bos_ref_rekening.nama_rekening', 'like', $cari)
              ->orWhere('bos_ref_kode.uraian_kode', 'like', $cari);
          });
        });
      return $q;
    };

    $totalJumlah = (float) $saring($dasar())->sum('bos_rapbs.jumlah');

    // Ringkasan per sumber dana: saringan yang sama kecuali
    // saringan sumber dana itu sendiri.
    $ringkasan = BosRapbs::query()
      ->from('bos_rapbs')
      ->join('bos_anggaran', function ($j) {
        $j->on('bos_anggaran.id_arkas', '=', 'bos_rapbs.id_anggaran_arkas')
          ->on('bos_anggaran.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoin('bos_ref_kode', function ($j) {
        $j->on('bos_ref_kode.id_arkas', '=', 'bos_rapbs.id_ref_kode')
          ->on('bos_ref_kode.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoin('bos_ref_rekening', function ($j) {
        $j->on('bos_ref_rekening.kode_rekening', '=', 'bos_rapbs.kode_rekening')
          ->on('bos_ref_rekening.sekolah_id', '=', 'bos_rapbs.sekolah_id')
          ->on('bos_ref_rekening.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->where('bos_rapbs.sekolah_id', $sekolahId)
      ->where('bos_rapbs.soft_delete', false)
      ->when($request->filled('tahun'), fn($x) => $x->where('bos_anggaran.tahun_anggaran', $request->integer('tahun')))
      ->when($request->filled('kegiatan'), fn($x) => $x->where('bos_rapbs.id_ref_kode', $request->input('kegiatan')))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('bos_rapbs.uraian', 'like', $cari)
            ->orWhere('bos_rapbs.kode_rekening', 'like', $cari)
            ->orWhere('bos_ref_rekening.nama_rekening', 'like', $cari)
            ->orWhere('bos_ref_kode.uraian_kode', 'like', $cari);
        });
      })
      ->groupBy('bos_anggaran.id_ref_sumber_dana', 'bos_anggaran.nama_sumber_dana')
      ->selectRaw('bos_anggaran.id_ref_sumber_dana, bos_anggaran.nama_sumber_dana, COUNT(*) as baris, SUM(bos_rapbs.jumlah) as total')
      ->orderBy('bos_anggaran.nama_sumber_dana')
      ->get();

    $kolomUrut = [
      'kegiatan' => 'bos_ref_kode.uraian_kode',
      'rekening' => 'bos_rapbs.kode_rekening',
      'uraian' => 'bos_rapbs.uraian',
      'volume' => 'bos_rapbs.volume',
      'harga' => 'bos_rapbs.harga_satuan',
      'jumlah' => 'bos_rapbs.jumlah',
    ];
    $urut = $kolomUrut[$request->input('urut')] ?? null;
    $urutKunci = $urut ? $request->input('urut') : 'kegiatan';
    $arah = $this->arah($request);

    $baris = $saring($dasar())
      ->select([
        'bos_rapbs.*',
        'bos_anggaran.tahun_anggaran',
        'bos_anggaran.nama_sumber_dana',
        'bos_ref_kode.id_kode as kode_kegiatan',
        'bos_ref_kode.uraian_kode as nama_kegiatan',
        'bos_ref_rekening.nama_rekening',
      ])
      ->orderBy($urut ?? 'bos_ref_kode.uraian_kode', $urut ? $arah : 'asc')
      ->orderBy('bos_rapbs.kode_rekening')
      ->orderBy('bos_rapbs.urutan')
      ->paginate(25)
      ->withQueryString();

    $daftarTahun = BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->distinct()
      ->orderByDesc('tahun_anggaran')
      ->pluck('tahun_anggaran')
      ->map(fn($t) => (int) $t)
      ->all();

    $daftarSumberDana = BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->select('id_ref_sumber_dana', 'nama_sumber_dana')
      ->distinct()
      ->orderBy('nama_sumber_dana')
      ->get();

    $daftarKegiatan = BosRefKode::where('sekolah_id', $sekolahId)
      ->whereIn('id_arkas', function ($q) use ($sekolahId) {
        $q->select('id_ref_kode')->from('bos_rapbs')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->orderBy('uraian_kode')
      ->get();

    return view('content.kelola-bos.kertas-kerja', $konteks + [
      'baris' => $baris,
      'totalJumlah' => $totalJumlah,
      'ringkasan' => $ringkasan,
      'daftarTahun' => $daftarTahun,
      'daftarSumberDana' => $daftarSumberDana,
      'daftarKegiatan' => $daftarKegiatan,
      'urut' => $urutKunci,
      'arah' => $arah,
    ]);
  }

  // ── BKU ──────────────────────────────────────────────────

  public function bku(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.bku', $konteks + [
        'baris' => null,
        'totalNominal' => 0,
        'daftarTahun' => [],
        'daftarJenis' => collect(),
        'daftarRekening' => collect(),
        'urut' => 'tanggal',
        'arah' => 'desc',
      ]);
    }

    $dasar = fn() => BosKasUmum::query()
      ->from('bos_kas_umum')
      ->leftJoin('bos_ref_bku', function ($j) {
        $j->on('bos_ref_bku.id_ref_bku', '=', 'bos_kas_umum.id_ref_bku')
          ->on('bos_ref_bku.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->leftJoin('bos_kas_nota', function ($j) {
        $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
          ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->where('bos_kas_umum.sekolah_id', $sekolahId)
      ->where('bos_kas_umum.soft_delete', false);

    $saring = function ($q) use ($request) {
      $q->when($request->filled('tahun'), fn($x) => $x->whereYear('bos_kas_umum.tanggal_transaksi', $request->integer('tahun')))
        ->when($request->filled('bulan'), fn($x) => $x->whereMonth('bos_kas_umum.tanggal_transaksi', $request->integer('bulan')))
        ->when($request->filled('jenis'), fn($x) => $x->where('bos_kas_umum.id_ref_bku', $request->integer('jenis')))
        ->when($request->filled('rekening'), fn($x) => $x->where('bos_kas_umum.kode_rekening', $request->input('rekening')))
        ->when($request->filled('cari'), function ($x) use ($request) {
          $cari = '%' . $request->input('cari') . '%';
          $x->where(function ($y) use ($cari) {
            $y->where('bos_kas_umum.uraian', 'like', $cari)
              ->orWhere('bos_kas_umum.no_bukti', 'like', $cari)
              ->orWhere('bos_kas_nota.nama_toko', 'like', $cari);
          });
        });
      return $q;
    };

    $totalNominal = (float) $saring($dasar())->sum('bos_kas_umum.saldo');

    $kolomUrut = [
      'tanggal' => 'bos_kas_umum.tanggal_transaksi',
      'bukti' => 'bos_kas_umum.no_bukti',
      'uraian' => 'bos_kas_umum.uraian',
      'rekening' => 'bos_kas_umum.kode_rekening',
      'nominal' => 'bos_kas_umum.saldo',
    ];
    $urut = $kolomUrut[$request->input('urut')] ?? null;
    $urutKunci = $urut ? $request->input('urut') : 'tanggal';
    $arah = $this->arah($request);

    // Nama rekening dipilih per tahun transaksi lewat subquery
    // (satu kode rekening punya nama per tahun di ref ARKAS).
    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
    $tahunTransaksi = $driver === 'mysql'
      ? 'YEAR(bos_kas_umum.tanggal_transaksi)'
      : "CAST(strftime('%Y', bos_kas_umum.tanggal_transaksi) AS INTEGER)";

    $baris = $saring($dasar())
      ->select([
        'bos_kas_umum.*',
        'bos_ref_bku.nama_bku',
        'bos_ref_bku.kode_bku',
        'bos_kas_nota.nama_toko',
      ])
      ->selectRaw("(SELECT nama_rekening FROM bos_ref_rekening r
        WHERE r.sekolah_id = bos_kas_umum.sekolah_id
          AND r.kode_rekening = bos_kas_umum.kode_rekening
          AND r.tahun = {$tahunTransaksi}
        LIMIT 1) as nama_rekening")
      ->orderBy($urut ?? 'bos_kas_umum.tanggal_transaksi', $urut ? $arah : 'desc')
      ->orderByDesc('bos_kas_umum.id')
      ->paginate(25)
      ->withQueryString();

    $daftarTahun = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->selectRaw(($driver === 'mysql'
        ? 'YEAR(tanggal_transaksi)'
        : "CAST(strftime('%Y', tanggal_transaksi) AS INTEGER)") . ' as tahun')
      ->distinct()
      ->orderByDesc('tahun')
      ->pluck('tahun')
      ->map(fn($t) => (int) $t)
      ->all();

    $daftarJenis = BosRefBku::where('sekolah_id', $sekolahId)
      ->whereIn('id_ref_bku', function ($q) use ($sekolahId) {
        $q->select('id_ref_bku')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->orderBy('nama_bku')
      ->get();

    $daftarRekening = BosRefRekening::where('sekolah_id', $sekolahId)
      ->whereIn('kode_rekening', function ($q) use ($sekolahId) {
        $q->select('kode_rekening')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)
          ->whereNotNull('kode_rekening')->distinct();
      })
      ->select('kode_rekening', 'nama_rekening')
      ->distinct()
      ->orderBy('kode_rekening')
      ->get();

    return view('content.kelola-bos.bku', $konteks + [
      'baris' => $baris,
      'totalNominal' => $totalNominal,
      'daftarTahun' => $daftarTahun,
      'daftarJenis' => $daftarJenis,
      'daftarRekening' => $daftarRekening,
      'urut' => $urutKunci,
      'arah' => $arah,
    ]);
  }
}
