<?php

namespace App\Http\Controllers;

use App\Models\BosAnggaran;
use App\Models\BosKasUmum;
use App\Models\BosRapbs;
use App\Models\BosRefAcuanBarang;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefRekening;
use App\Models\BosSinkronLog;
use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kelola BOS (fitur: kelola-bos) — tampilan read-only cermin
 * data ARKAS. Halaman: Kertas Kerja (bos_rapbs, dengan
 * pemilih versi RKAS), BKU (bos_kas_umum), Perbandingan
 * Versi (selisih dua versi kertas kerja — lembar kerja
 * pergeseran), dan Referensi kode (barang/kegiatan/
 * rekening). Data TIDAK diubah dari sini; ia datang dari
 * agen sinkron di laptop ARKAS.
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

  private function daftarTahun(int $sekolahId): array
  {
    return BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->distinct()
      ->orderByDesc('tahun_anggaran')
      ->pluck('tahun_anggaran')
      ->map(fn($t) => (int) $t)
      ->all();
  }

  private function daftarSumberDana(int $sekolahId)
  {
    return BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->select('id_ref_sumber_dana', 'nama_sumber_dana')
      ->distinct()
      ->orderBy('nama_sumber_dana')
      ->get();
  }

  // ── Versi kertas kerja ─────────────────────────────────
  //
  // Di ARKAS, setiap revisi RKAS adalah salinan utuh kertas
  // kerja dalam baris anggaran tersendiri. Versi-versi per
  // (tahun, sumber dana) diurutkan menurut tanggal buat
  // ARKAS (arkas_create_date, cadangan is_revisi) dan
  // dilabeli berurutan: RKAS Awal, Revisi 1, Revisi 2, ...

  /** @return array<string, array{tahun:int, sumber:int, nama_sumber:?string, versi:\Illuminate\Support\Collection}> */
  private function kelompokVersi(int $sekolahId): array
  {
    $semua = BosAnggaran::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->orderBy('tahun_anggaran')
      ->orderBy('id_ref_sumber_dana')
      ->orderByRaw('CASE WHEN arkas_create_date IS NULL THEN 1 ELSE 0 END')
      ->orderBy('arkas_create_date')
      ->orderBy('is_revisi')
      ->orderBy('id')
      ->get();

    $kelompok = [];
    foreach ($semua as $a) {
      $kunci = $a->tahun_anggaran . '|' . $a->id_ref_sumber_dana;
      if (!isset($kelompok[$kunci])) {
        $kelompok[$kunci] = [
          'tahun' => (int) $a->tahun_anggaran,
          'sumber' => (int) $a->id_ref_sumber_dana,
          'nama_sumber' => $a->nama_sumber_dana,
          'versi' => collect(),
        ];
      }
      $kelompok[$kunci]['versi']->push($a);
    }
    foreach ($kelompok as $k) {
      foreach ($k['versi'] as $i => $a) {
        $a->label_versi = $i === 0 ? 'RKAS Awal' : 'Revisi ' . $i;
      }
    }
    return $kelompok;
  }

  /**
   * Tentukan id_arkas anggaran yang dipakai untuk cakupan
   * (tahun, sumber dana). $versiParam: null/kosong = bawaan
   * (versi disetujui terakhir per kelompok, cadangan versi
   * terakhir), 'semua' = semua versi, selain itu id_arkas
   * satu versi tertentu (kelompok lain tetap bawaan).
   *
   * @return string[]
   */
  private function pilihVersiIds(array $kelompok, ?int $tahun, ?int $sumber, ?string $versiParam): array
  {
    $ids = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      if ($sumber !== null && $k['sumber'] !== $sumber) {
        continue;
      }
      $daftar = $k['versi'];
      if ($versiParam === 'semua') {
        foreach ($daftar as $a) {
          $ids[] = $a->id_arkas;
        }
        continue;
      }
      if ($versiParam) {
        $cocok = $daftar->firstWhere('id_arkas', $versiParam);
        if ($cocok) {
          $ids[] = $cocok->id_arkas;
          continue;
        }
      }
      $disetujui = $daftar->filter(fn($a) => (int) $a->is_approve === 1)->last();
      $pilih = $disetujui ?: $daftar->last();
      if ($pilih) {
        $ids[] = $pilih->id_arkas;
      }
    }
    return $ids;
  }

  /** Pilihan untuk select Versi (kelompok dalam cakupan tahun/sumber). */
  private function pilihanVersi(array $kelompok, ?int $tahun, ?int $sumber): array
  {
    $pilihan = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      if ($sumber !== null && $k['sumber'] !== $sumber) {
        continue;
      }
      foreach ($k['versi'] as $a) {
        $pilihan[] = [
          'id_arkas' => $a->id_arkas,
          'label' => $k['tahun'] . ' · ' . ($k['nama_sumber'] ?? 'Sumber #' . $k['sumber'])
            . ' — ' . $a->label_versi,
          'disetujui' => (int) $a->is_approve === 1,
        ];
      }
    }
    return $pilihan;
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
        'daftarVersi' => [],
        'urut' => 'kegiatan',
        'arah' => 'asc',
      ]);
    }

    $tahunReq = $request->filled('tahun') ? $request->integer('tahun') : null;
    $sumberReq = $request->filled('sumber_dana') ? $request->integer('sumber_dana') : null;
    $versiParam = $request->filled('versi') ? (string) $request->input('versi') : null;

    $kelompok = $this->kelompokVersi($sekolahId);
    $idsUtama = $this->pilihVersiIds($kelompok, $tahunReq, $sumberReq, $versiParam);
    // Ringkasan per sumber dana tidak ikut saringan sumber
    // dana, tetapi mengikuti pilihan versi & tahun.
    $idsRingkasan = $this->pilihVersiIds($kelompok, $tahunReq, null, $versiParam);

    // ref_kode ARKAS punya banyak varian per (id, tahun)
    // untuk sumber dana/bentuk pendidikan berbeda; uraian
    // & id_kode-nya sama, jadi join memakai subquery
    // distinct agar baris rapbs tidak berlipat.
    $refKodeDistinct = BosRefKode::query()
      ->select('sekolah_id', 'id_ref_kode', 'tahun')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('sekolah_id', 'id_ref_kode', 'tahun');

    $dasar = fn() => BosRapbs::query()
      ->from('bos_rapbs')
      ->join('bos_anggaran', function ($j) {
        $j->on('bos_anggaran.id_arkas', '=', 'bos_rapbs.id_anggaran_arkas')
          ->on('bos_anggaran.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoinSub($refKodeDistinct, 'bos_ref_kode', function ($j) {
        $j->on('bos_ref_kode.id_ref_kode', '=', 'bos_rapbs.id_ref_kode')
          ->on('bos_ref_kode.sekolah_id', '=', 'bos_rapbs.sekolah_id')
          ->on('bos_ref_kode.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->leftJoin('bos_ref_rekening', function ($j) {
        $j->on('bos_ref_rekening.kode_rekening', '=', 'bos_rapbs.kode_rekening')
          ->on('bos_ref_rekening.sekolah_id', '=', 'bos_rapbs.sekolah_id')
          ->on('bos_ref_rekening.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->where('bos_rapbs.sekolah_id', $sekolahId)
      ->where('bos_rapbs.soft_delete', false);

    $saring = function ($q, array $idsVersi) use ($request) {
      $q->whereIn('bos_rapbs.id_anggaran_arkas', $idsVersi ?: ['__tidak_ada__'])
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

    $totalJumlah = (float) $saring($dasar(), $idsUtama)->sum('bos_rapbs.jumlah');

    // Ringkasan per sumber dana: saringan yang sama kecuali
    // saringan sumber dana itu sendiri.
    $ringkasan = $saring($dasar(), $idsRingkasan)
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

    $baris = $saring($dasar(), $idsUtama)
      ->select([
        'bos_rapbs.*',
        'bos_anggaran.tahun_anggaran',
        'bos_anggaran.nama_sumber_dana',
        'bos_ref_kode.id_kode as kode_kegiatan',
        'bos_ref_kode.uraian_kode as nama_kegiatan',
      ])
      ->selectRaw('bos_ref_rekening.nama_rekening as nama_rekening')
      ->orderBy($urut ?? 'bos_ref_kode.uraian_kode', $urut ? $arah : 'asc')
      ->orderBy('bos_rapbs.kode_rekening')
      ->orderBy('bos_rapbs.urutan')
      ->paginate(25)
      ->withQueryString();

    $daftarKegiatan = BosRefKode::where('sekolah_id', $sekolahId)
      ->whereNotNull('id_ref_kode')
      ->whereIn('id_ref_kode', function ($q) use ($sekolahId) {
        $q->select('id_ref_kode')->from('bos_rapbs')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->select('id_ref_kode')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode')
      ->orderBy('uraian_kode')
      ->get();

    return view('content.kelola-bos.kertas-kerja', $konteks + [
      'baris' => $baris,
      'totalJumlah' => $totalJumlah,
      'ringkasan' => $ringkasan,
      'daftarTahun' => $this->daftarTahun($sekolahId),
      'daftarSumberDana' => $this->daftarSumberDana($sekolahId),
      'daftarKegiatan' => $daftarKegiatan,
      'daftarVersi' => $this->pilihanVersi($kelompok, $tahunReq, $sumberReq),
      'urut' => $urutKunci,
      'arah' => $arah,
    ]);
  }

  // ── Perbandingan Versi ───────────────────────────────────

  public function perbandingan(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    $kosong = [
      'hasil' => null,
      'daftarTahun' => $sekolahId ? $this->daftarTahun($sekolahId) : [],
      'daftarSumberDana' => $sekolahId ? $this->daftarSumberDana($sekolahId) : collect(),
      'daftarVersi' => [],
    ];
    if (!$sekolahId) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }

    $tahunReq = $request->filled('tahun') ? $request->integer('tahun') : null;
    $sumberReq = $request->filled('sumber_dana') ? $request->integer('sumber_dana') : null;
    $kelompok = $this->kelompokVersi($sekolahId);
    $kosong['daftarVersi'] = $this->pilihanVersi($kelompok, $tahunReq, $sumberReq);

    $idA = (string) $request->input('versi_a', '');
    $idB = (string) $request->input('versi_b', '');
    if (!$idA || !$idB || $idA === $idB) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }

    $versiA = BosAnggaran::where('sekolah_id', $sekolahId)->where('id_arkas', $idA)->first();
    $versiB = BosAnggaran::where('sekolah_id', $sekolahId)->where('id_arkas', $idB)->first();
    if (!$versiA || !$versiB) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }
    // Label versi dari kelompoknya masing-masing.
    foreach ($kelompok as $k) {
      foreach ($k['versi'] as $a) {
        if ($a->id_arkas === $idA) {
          $versiA->label_versi = $a->label_versi;
        }
        if ($a->id_arkas === $idB) {
          $versiB->label_versi = $a->label_versi;
        }
      }
    }

    $agregat = function (string $idArkas) use ($sekolahId) {
      return BosRapbs::where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_anggaran_arkas', $idArkas)
        ->groupBy('id_ref_kode', 'kode_rekening', 'uraian')
        ->selectRaw('id_ref_kode, kode_rekening, uraian, SUM(volume) as volume, SUM(jumlah) as jumlah, COUNT(*) as baris')
        ->get()
        ->keyBy(fn($r) => $r->id_ref_kode . '|' . $r->kode_rekening . '|' . $r->uraian);
    };
    $aggA = $agregat($idA);
    $aggB = $agregat($idB);

    $tahunBanding = (int) $versiA->tahun_anggaran;
    $petaKegiatan = BosRefKode::where('sekolah_id', $sekolahId)
      ->where('tahun', $tahunBanding)
      ->whereNotNull('id_ref_kode')
      ->select('id_ref_kode')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode')
      ->get()
      ->keyBy('id_ref_kode');
    $petaRekening = BosRefRekening::where('sekolah_id', $sekolahId)
      ->where('tahun', $tahunBanding)
      ->pluck('nama_rekening', 'kode_rekening');

    $hitung = ['berubah' => 0, 'baru' => 0, 'hilang' => 0, 'tetap' => 0];
    $perKegiatan = [];
    foreach ($aggA->keys()->merge($aggB->keys())->unique() as $kunci) {
      $a = $aggA->get($kunci);
      $b = $aggB->get($kunci);
      $acuan = $a ?: $b;
      $volA = $a ? (float) $a->volume : 0.0;
      $volB = $b ? (float) $b->volume : 0.0;
      $jumA = $a ? (float) $a->jumlah : 0.0;
      $jumB = $b ? (float) $b->jumlah : 0.0;
      $kelas = match (true) {
        $a === null => 'baru',
        $b === null => 'hilang',
        $volA !== $volB || $jumA !== $jumB => 'berubah',
        default => 'tetap',
      };
      $hitung[$kelas]++;

      $idKegiatan = (string) $acuan->id_ref_kode;
      if (!isset($perKegiatan[$idKegiatan])) {
        $perKegiatan[$idKegiatan] = [
          'id_kode' => $petaKegiatan->get($idKegiatan)->id_kode ?? '',
          'uraian_kode' => $petaKegiatan->get($idKegiatan)->uraian_kode ?? 'Kegiatan lain-lain',
          'totalA' => 0.0,
          'totalB' => 0.0,
          'baris' => [],
        ];
      }
      $perKegiatan[$idKegiatan]['totalA'] += $jumA;
      $perKegiatan[$idKegiatan]['totalB'] += $jumB;
      if ($kelas !== 'tetap') {
        $perKegiatan[$idKegiatan]['baris'][] = [
          'uraian' => $acuan->uraian,
          'kode_rekening' => $acuan->kode_rekening,
          'nama_rekening' => $petaRekening->get($acuan->kode_rekening),
          'volume_a' => $volA,
          'volume_b' => $volB,
          'jumlah_a' => $jumA,
          'jumlah_b' => $jumB,
          'selisih' => $jumB - $jumA,
          'kelas' => $kelas,
        ];
      }
    }
    // Urutkan kegiatan menurut nama; baris di dalamnya menurut uraian.
    $perKegiatan = collect($perKegiatan)
      ->sortBy('uraian_kode')
      ->map(function ($k) {
        $k['baris'] = collect($k['baris'])->sortBy('uraian')->values()->all();
        return $k;
      })
      ->values()
      ->all();

    $totalA = (float) $aggA->sum('jumlah');
    $totalB = (float) $aggB->sum('jumlah');

    $kosong['hasil'] = [
      'versiA' => $versiA,
      'versiB' => $versiB,
      'totalA' => $totalA,
      'totalB' => $totalB,
      'selisih' => $totalB - $totalA,
      'hitung' => $hitung,
      'perKegiatan' => $perKegiatan,
    ];
    return view('content.kelola-bos.perbandingan', $konteks + $kosong);
  }

  // ── Referensi: Kode Barang ───────────────────────────────

  public function referensiBarang(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-barang', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
        'daftarBlok' => [],
        'urut' => 'nama',
        'arah' => 'asc',
      ]);
    }

    $kolomUrut = ['nama' => 'nama_barang', 'harga' => 'harga_barang'];
    $urutReq = $request->input('urut');
    $urutKunci = isset($kolomUrut[$urutReq]) ? $urutReq : 'nama';
    $arah = $this->arah($request);

    $baris = BosRefAcuanBarang::where('sekolah_id', $sekolahId)
      ->when($request->filled('tahun'), fn($x) => $x->where('tahun', $request->integer('tahun')))
      ->when($request->filled('blok'), fn($x) => $x->where('blok_id', 'like', $request->input('blok') . '%'))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('nama_barang', 'like', $cari)
            ->orWhere('id_barang', 'like', $cari)
            ->orWhere('kode_rekening', 'like', $cari);
        });
      })
      ->orderBy($kolomUrut[$urutKunci], $arah)
      ->orderBy('nama_barang')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-barang', $konteks + [
      'baris' => $baris,
      'daftarTahun' => BosRefAcuanBarang::where('sekolah_id', $sekolahId)
        ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all(),
      'daftarBlok' => BosRefAcuanBarang::where('sekolah_id', $sekolahId)
        ->distinct()->orderBy('blok_id')->pluck('blok_id')->all(),
      'urut' => $urutKunci,
      'arah' => $arah,
    ]);
  }

  // ── Referensi: Kode Kegiatan ─────────────────────────────

  public function referensiKegiatan(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-kegiatan', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
      ]);
    }

    // ref_kode tersimpan per varian (sumber dana/bentuk);
    // untuk referensi cukup tampilkan baris unik per
    // (id_kode, uraian, level, tahun).
    $baris = BosRefKode::where('sekolah_id', $sekolahId)
      ->whereNotNull('id_kode')
      ->when($request->filled('tahun'), fn($x) => $x->where('tahun', $request->integer('tahun')))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('uraian_kode', 'like', $cari)
            ->orWhere('id_kode', 'like', $cari);
        });
      })
      ->select('id_kode', 'uraian_kode', 'id_level_kode', 'tahun')
      ->groupBy('id_kode', 'uraian_kode', 'id_level_kode', 'tahun')
      ->orderBy('tahun')
      ->orderBy('id_kode')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-kegiatan', $konteks + [
      'baris' => $baris,
      'daftarTahun' => BosRefKode::where('sekolah_id', $sekolahId)
        ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all(),
    ]);
  }

  // ── Referensi: Kode Rekening ─────────────────────────────

  public function referensiRekening(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    if (!$sekolahId) {
      return view('content.kelola-bos.referensi-rekening', $konteks + [
        'baris' => null,
        'daftarTahun' => [],
      ]);
    }

    $baris = BosRefRekening::where('sekolah_id', $sekolahId)
      ->when($request->filled('tahun'), fn($x) => $x->where('tahun', $request->integer('tahun')))
      ->when($request->filled('cari'), function ($x) use ($request) {
        $cari = '%' . $request->input('cari') . '%';
        $x->where(function ($y) use ($cari) {
          $y->where('kode_rekening', 'like', $cari)
            ->orWhere('nama_rekening', 'like', $cari);
        });
      })
      ->orderBy('kode_rekening')
      ->orderBy('tahun')
      ->paginate(25)
      ->withQueryString();

    return view('content.kelola-bos.referensi-rekening', $konteks + [
      'baris' => $baris,
      'daftarTahun' => BosRefRekening::where('sekolah_id', $sekolahId)
        ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all(),
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
