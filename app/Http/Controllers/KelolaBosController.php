<?php

namespace App\Http\Controllers;

use App\Models\BosAnggaran;
use App\Models\BosKasNota;
use App\Models\BosKasUmum;
use App\Models\BosNamaAlias;
use App\Models\BosRapbs;
use App\Models\BosRapbsPeriode;
use App\Models\BosRefAcuanBarang;
use App\Models\BosRefBku;
use App\Models\BosRefKode;
use App\Models\BosRefRekening;
use App\Models\BosSinkronLog;
use App\Models\BosSinkronToken;
use App\Models\BosSpjGrup;
use App\Models\BosSpjGrupAnggota;
use App\Models\BosSpjGrupManual;
use App\Models\BosSpjPenerima;
use App\Models\Penerima;
use App\Models\Ptk;
use App\Models\Sekolah;
use App\Support\Terbilang;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Kelola BOS (fitur: kelola-bos) — tampilan read-only cermin
 * data ARKAS. Halaman: Kertas Kerja (bos_rapbs, dengan
 * pemilih versi RKAS serta — Fase 1.6 — jadwal bulanan dari
 * bos_rapbs_periode dan status realisasi BKU per baris), BKU
 * (bos_kas_umum), Perbandingan Versi (selisih dua versi
 * kertas kerja — lembar kerja pergeseran), dan Referensi
 * kode (barang/kegiatan/rekening). Cermin TIDAK diubah
 * dari sini; ia datang dari agen sinkron di laptop ARKAS.
 * Satu-satunya penulisan adalah nama alias (Fase 1.7) —
 * lapisan kerja milik pengguna, terpisah dari cermin.
 * Fase 1.12 menambah satu penulisan lagi yang juga
 * lapisan kerja: pembuatan token agen sinkron dari web
 * (sebelumnya hanya lewat perintah `bos:token`).
 * Fase 1.10: Referensi Kegiatan dibatasi ke jenjang
 * sekolah (dari pemakaian rapbs, cadangan jenjang
 * tabel_sekolah) dan halaman-halaman dibuka dengan
 * bawaan tahun berjalan + BOS Reguler + versi terakhir
 * disetujui bila parameternya tidak dikirim.
 *
 * Operator Sekolah, Kepala Sekolah & Bendahara (hanya
 * membaca) melihat sekolahnya; Admin memantau lewat
 * pemilih sekolah (pola Surat Keluar).
 */
class KelolaBosController extends Controller
{
  /** Nama bulan untuk pemilih jadwal (id_periode ARKAS 81–92). */
  private const NAMA_BULAN = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
  ];

  /** Jenis BKU belanja (Kas Keluar dkk.) untuk realisasi. */
  private const JENIS_BELANJA = [4, 15, 24, 35];

  /**
   * Pemetaan jenis BKU (id_ref_bku ARKAS) untuk kartu dana di halaman
   * BKU: id => tanda (+1 menambah, -1 mengurangi). Kolom saldo di cermin
   * BKU tidak bertanda, jadi arah tiap jenis ditetapkan di sini — sudah
   * diuji terhadap saldo bawaan antarbulan ARKAS (data 2024–2026 cocok
   * di semua bulan). Ubah di sini bila ada jenis baru.
   *
   * Tunai : 9 Saldo Awal Tunai (+), 12 Pergeseran Tunai (+),
   *         4 Kas Keluar (−)
   * Bank  : 8 Saldo Awal Bank (+), 2 Terima Dana BOS (+), 6 Bunga Bank (+),
   *         7 Pajak Bunga (−), 3 Tarik Tunai (−),
   *         15 Kas Keluar Non Tunai (−)
   *
   * Penting: "Saldo Awal" (8/9) bukan saldo awal tahun, melainkan saldo
   * penutup bulan sebelumnya yang dibawa ARKAS ke tiap bulan, per
   * anggaran (sumber dana). Menjumlahkannya lintas bulan salah; yang
   * dipakai hanya baris saldo awal TERAKHIR tiap anggaran, ditambah
   * transaksi sesudahnya (lihat hitungKartuDana).
   *
   * Pajak : jenis 10 Pajak Belanja Terima — hanya ditampilkan sebagai
   *         jumlah pajak, tidak ikut menghitung dana.
   */
  private const DANA_TUNAI = [9 => 1, 12 => 1, 4 => -1];
  private const DANA_BANK = [8 => 1, 2 => 1, 6 => 1, 7 => -1, 3 => -1, 15 => -1];
  private const JENIS_PAJAK = 10;
  /** Jenis yang dikenal tetapi tidak memengaruhi saldo kas (pajak terima/setor). */
  private const DANA_DIABAIKAN = [10, 11];

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

  // ── Nama alias (Fase 1.7, lapisan kerja) ──────────────
  //
  // Alias adalah nama pendek buatan pengguna untuk nama
  // asli ARKAS yang panjang. Peta alias untuk nama-nama di
  // satu halaman diambil dengan SATU query (whereIn atas
  // nama yang tampil), bukan query per baris.

  /** @return array<string, string> peta nama_sumber => alias */
  private function petaAlias(int $sekolahId, $daftarNama): array
  {
    $nama = collect($daftarNama)
      ->filter(fn($n) => is_string($n) && $n !== '')
      ->unique()
      ->values();
    if ($nama->isEmpty()) {
      return [];
    }
    return BosNamaAlias::where('sekolah_id', $sekolahId)
      ->whereIn('nama_sumber', $nama)
      ->pluck('alias', 'nama_sumber')
      ->all();
  }

  /** Alias hanya boleh ditulis Admin & Operator Sekolah. */
  private function bisaTulisAlias(): bool
  {
    return Auth::user()->hasAnyRole(['Admin', 'Operator Sekolah']);
  }

  /**
   * Simpan / hapus alias. Alias terisi → updateOrCreate;
   * alias kosong → baris dihapus (tampilan kembali ke
   * nama asli dari ARKAS).
   */
  public function simpanAlias(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Konteks sekolah belum dipilih — alias tidak disimpan.');
    }

    $data = $request->validate([
      'nama_sumber' => ['required', 'string', 'max:500'],
      'alias' => ['nullable', 'string', 'max:255'],
    ]);

    $alias = trim((string) ($data['alias'] ?? ''));
    if ($alias === '') {
      BosNamaAlias::where('sekolah_id', $sekolahId)
        ->where('nama_sumber', $data['nama_sumber'])
        ->delete();
      return back()->with('success', 'Alias dihapus — nama asli dari ARKAS ditampilkan lagi.');
    }

    BosNamaAlias::updateOrCreate(
      ['sekolah_id' => $sekolahId, 'nama_sumber' => $data['nama_sumber']],
      ['alias' => $alias]
    );
    return back()->with('success', 'Alias "' . $alias . '" disimpan.');
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

  // ── Bawaan halaman (Fase 1.10) ──────────────────────
  //
  // Halaman Kelola BOS dibuka dengan pilihan yang paling
  // sering dipakai: tahun berjalan dan — untuk Kertas
  // Kerja & Perbandingan — BOS Reguler serta versi
  // terakhir disetujui. Bawaan hanya berlaku bila
  // parameternya TIDAK dikirim sama sekali; pilihan
  // eksplisit pengguna (termasuk memilih "semua" yang
  // mengirim parameter kosong) selalu menang.

  /** Tahun bawaan dari daftar tahun yang tersedia. */
  private function tahunBawaan(array $daftarTahun): ?int
  {
    if (!$daftarTahun) {
      return null;
    }
    $tahunIni = (int) now()->format('Y');
    return in_array($tahunIni, $daftarTahun, true) ? $tahunIni : max($daftarTahun);
  }

  /**
   * Tahun efektif sebuah halaman: nilai parameter bila
   * diisi; null (semua tahun) bila parameter dikirim
   * kosong secara eksplisit; bawaan bila parameter
   * tidak dikirim sama sekali.
   */
  private function tahunEfektif(Request $request, array $daftarTahun): ?int
  {
    if ($request->filled('tahun')) {
      return $request->integer('tahun');
    }
    if ($request->has('tahun')) {
      return null;
    }
    return $this->tahunBawaan($daftarTahun);
  }

  /**
   * Sumber dana bawaan untuk satu tahun dari kelompok
   * versi: BOS Reguler (id_ref_sumber_dana 1) bila ada,
   * selain itu sumber pertama yang tersedia tahun itu.
   */
  private function sumberBawaan(array $kelompok, ?int $tahun): ?int
  {
    $ada = [];
    foreach ($kelompok as $k) {
      if ($tahun !== null && $k['tahun'] !== $tahun) {
        continue;
      }
      $ada[] = $k['sumber'];
    }
    if (!$ada) {
      return null;
    }
    return in_array(1, $ada, true) ? 1 : $ada[0];
  }

  // ── Jenjang sekolah (Fase 1.10) ─────────────────────
  //
  // bentuk_pendidikan_id sekolah ini, untuk membatasi
  // Referensi Kegiatan ke jenjangnya sendiri. Urutan
  // penurunan:
  //  1. Dari pemakaian nyata: varian ref_kode yang
  //     benar-benar dipakai baris rapbs sekolah ini —
  //     varian dibedakan lewat tahun anggaran baris
  //     (id_ref_tahun_anggaran) dan sumber dana
  //     anggarannya; bentuk yang paling banyak dipakai
  //     menang. Ini bukti terkuat.
  //  2. Cadangan: tabel_sekolah.jenjang dipetakan ke id
  //     bentuk ARKAS — hanya pasangan yang terbukti dari
  //     data ('SD' → 5). Jenjang lain sengaja TIDAK
  //     dipetakan: ARKAS tidak menyediakan tabel padanan
  //     dan id bentuknya belum terverifikasi.
  //  3. Keduanya kosong → null (tanpa pembatasan).

  private function bentukSekolah(int $sekolahId): ?int
  {
    $baris = BosRefKode::query()
      ->from('bos_ref_kode as rk')
      ->join('bos_rapbs as rp', function ($j) {
        $j->on('rp.id_ref_kode', '=', 'rk.id_ref_kode')
          ->on('rp.id_ref_tahun_anggaran', '=', 'rk.tahun');
      })
      ->join('bos_anggaran as ag', function ($j) {
        $j->on('ag.id_arkas', '=', 'rp.id_anggaran_arkas')
          ->on('ag.sekolah_id', '=', 'rp.sekolah_id')
          ->on('ag.id_ref_sumber_dana', '=', 'rk.sumber_dana_id');
      })
      ->where('rp.sekolah_id', $sekolahId)
      ->where('rp.soft_delete', false)
      ->whereNotNull('rk.bentuk_pendidikan_id')
      ->selectRaw('rk.bentuk_pendidikan_id as bentuk, COUNT(*) as terpakai')
      ->groupBy('rk.bentuk_pendidikan_id')
      ->orderByDesc('terpakai')
      ->first();
    if ($baris) {
      return (int) $baris->bentuk;
    }

    $jenjang = strtoupper(trim((string) (Sekolah::find($sekolahId)?->jenjang ?? '')));
    return ['SD' => 5][$jenjang] ?? null;
  }

  // ── Lingkup referensi (Fase 1.11) ───────────────────
  //
  // Halaman referensi dibatasi ke yang benar-benar bisa
  // dibelanjakan sekolah ini: kegiatan menurut jenjang
  // (Fase 1.10) DAN sumber dananya; barang & rekening
  // menurut rekening yang pernah dipakai kertas
  // kerjanya. Cermin tetap lengkap — semua ini murni
  // saringan tampil, dan selalu ada jalan melepasnya
  // (?semua=1).

  /**
   * Sumber dana yang dimiliki sekolah ini: sumber dari
   * anggarannya pada tahun terpilih; bila tahun itu
   * tidak punya anggaran, sumber dari semua tahun.
   * Hasil kosong berarti tanpa pembatasan sumber.
   */
  private function sumberSekolah(int $sekolahId, ?int $tahun): array
  {
    $ambil = fn(?int $th) => BosAnggaran::where('sekolah_id', $sekolahId)
      ->when($th !== null, fn($x) => $x->where('tahun_anggaran', $th))
      ->distinct()->pluck('id_ref_sumber_dana')
      ->map(fn($s) => (int) $s)->all();
    $sumber = $tahun !== null ? $ambil($tahun) : [];
    return $sumber ?: $ambil(null);
  }

  /**
   * Kode rekening yang pernah dipakai baris kertas
   * kerja sekolah ini, semua tahun. Baris ter-soft-
   * delete ikut dihitung: pada cermin ARKAS, soft-
   * delete di rapbs umumnya menandai salinan versi
   * yang tergantikan — rekeningnya tetap pernah resmi
   * dianggarkan sekolah ini. Hasil kosong berarti
   * tanpa pembatasan rekening.
   */
  private function rekeningTerpakai(int $sekolahId): array
  {
    return BosRapbs::where('sekolah_id', $sekolahId)
      ->whereNotNull('kode_rekening')
      ->distinct()->pluck('kode_rekening')->all();
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
        'daftarBulan' => self::NAMA_BULAN,
        'bulan' => (int) now()->format('n'),
        'bulanPotong' => (int) now()->format('n'),
        'tahunAcuan' => (int) now()->format('Y'),
        'tampilan' => 'posisi',
        'totalRencana' => 0,
        'statusFilter' => '',
        'kartu' => ['terjadwal' => 0.0, 'realisasi' => 0.0, 'sisa' => 0.0],
        'urut' => 'kegiatan',
        'arah' => 'asc',
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'tahunTerpilih' => null,
        'sumberTerpilih' => null,
      ]);
    }

    // Bawaan Fase 1.10: tanpa parameter tahun → tahun
    // berjalan (cadangan tahun terbesar yang ada); tanpa
    // parameter sumber dana → BOS Reguler bila ada pada
    // tahun efektif. Parameter yang dikirim kosong secara
    // eksplisit ("semua") tetap dihormati.
    $daftarTahun = $this->daftarTahun($sekolahId);
    $tahunReq = $this->tahunEfektif($request, $daftarTahun);
    $kelompok = $this->kelompokVersi($sekolahId);
    $sumberReq = $request->filled('sumber_dana')
      ? $request->integer('sumber_dana')
      : ($request->has('sumber_dana') ? null : $this->sumberBawaan($kelompok, $tahunReq));
    $versiParam = $request->filled('versi') ? (string) $request->input('versi') : null;

    // ── Tiga tampilan Kertas Kerja (Fase 1.8) ──
    // Halaman ini punya tiga tampilan:
    //  - posisi  : "Posisi s.d. Bulan Ini" — semantik
    //    kumulatif Fase 1.6, tetapi titik potong bulannya
    //    OTOMATIS: tahun berjalan → bulan ini, tahun
    //    lampau → Desember, tahun depan → belum ada
    //    bulan berjalan (potong 0). Tanpa pemilih bulan.
    //  - belanja : "Bisa Dibelanjakan" — agregat kumulatif
    //    yang sama dengan titik potong yang sama, tetapi
    //    hanya baris yang masih punya sisa untuk
    //    dibelanjakan (jadwal kumulatif > 0 dan sisa > 0),
    //    termasuk tunggakan bulan-bulan lalu yang belum
    //    masuk BKU.
    //  - rencana : "Rencana Bulanan" — satu-satunya
    //    tampilan berpemilih bulan: rencana anggaran bulan
    //    pilihan (bulan depan, bulan-bulan berikutnya,
    //    maupun bulan yang sudah lewat) dengan tanda
    //    sudah/belum dibelanjakan per baris.
    $tampilan = in_array($request->input('tampilan'), ['posisi', 'belanja', 'rencana'], true)
      ? (string) $request->input('tampilan')
      : 'posisi';

    $tahunIni = (int) now()->format('Y');
    $tahunAcuan = $tahunReq ?? $tahunIni;
    $bulanPotong = $tahunAcuan === $tahunIni
      ? (int) now()->format('n')
      : ($tahunAcuan < $tahunIni ? 12 : 0);

    if ($tampilan === 'rencana') {
      $bulanBawaan = $tahunAcuan === $tahunIni
        ? min((int) now()->format('n') + 1, 12)
        : 1;
      $bulan = $request->filled('bulan')
        ? max(1, min(12, $request->integer('bulan')))
        : $bulanBawaan;
    } else {
      $bulan = $bulanPotong;
    }

    $statusFilter = in_array($request->input('status'), ['sudah', 'sebagian', 'belum', 'belum_terjadwal'], true)
      ? (string) $request->input('status')
      : '';
    if ($tampilan === 'belanja') {
      // Pada tampilan belanja status tersirat dari
      // saringannya sendiri (masih ada sisa) — saringan
      // status disembunyikan dan tidak diterapkan.
      $statusFilter = '';
    }

    $idsUtama = $this->pilihVersiIds($kelompok, $tahunReq, $sumberReq, $versiParam);
    // Ringkasan per sumber dana tidak ikut saringan sumber
    // dana, tetapi mengikuti pilihan versi & tahun.
    $idsRingkasan = $this->pilihVersiIds($kelompok, $tahunReq, null, $versiParam);

    // ref_kode ARKAS punya banyak varian per (id, tahun)
    // untuk sumber dana/bentuk pendidikan berbeda; uraian
    // & id_kode-nya sama, jadi join memakai subquery
    // distinct agar baris rapbs tidak berlipat.
    $refKodeDistinct = BosRefKode::query()
      ->select('id_ref_kode', 'tahun')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode', 'tahun');

    $dasar = fn() => BosRapbs::query()
      ->from('bos_rapbs')
      ->join('bos_anggaran', function ($j) {
        $j->on('bos_anggaran.id_arkas', '=', 'bos_rapbs.id_anggaran_arkas')
          ->on('bos_anggaran.sekolah_id', '=', 'bos_rapbs.sekolah_id');
      })
      ->leftJoinSub($refKodeDistinct, 'bos_ref_kode', function ($j) {
        $j->on('bos_ref_kode.id_ref_kode', '=', 'bos_rapbs.id_ref_kode')
          ->on('bos_ref_kode.tahun', '=', 'bos_rapbs.id_ref_tahun_anggaran');
      })
      ->leftJoin('bos_ref_rekening', function ($j) {
        $j->on('bos_ref_rekening.kode_rekening', '=', 'bos_rapbs.kode_rekening')
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

    // ── Agregat jadwal & realisasi per baris ──
    // Jadwal: jumlah bos_rapbs_periode (baris bulanan saja,
    // id_periode 81–92; baris Triwulan 1–4 diabaikan untuk
    // tampilan bulanan) milik baris rapbs ini. Tampilan
    // posisi & belanja menjumlah Januari s.d. bulan potong
    // otomatis; tampilan rencana hanya bulan pilihannya.
    // Realisasi: saldo BKU belanja (jenis 4/15/24/35) yang
    // terhubung ke baris periode milik baris rapbs ini
    // lewat bos_kas_umum.id_rapbs_periode — jadi realisasi
    // selalu menempel ke versi tempat BKU diinput di ARKAS.
    // Setiap pembayaran BKU menempel ke SATU baris periode
    // bulanan tertentu (jadwal bulan mundur boleh dibayar
    // terlambat; jadwal bulan depan tidak bisa dibayar
    // lebih dulu), jadi:
    //  - posisi & belanja: realisasi adalah seluruh
    //    pembayaran baris ini, tanggal terakhir sebaris
    //    penuh (Fase 1.6/1.7).
    //  - rencana: realisasi & tanggal terakhir hanya dari
    //    pembayaran yang terhubung ke baris periode bulan
    //    pilihan (atribusi per bulan, Fase 1.7/1.8).
    if ($tampilan === 'rencana') {
      $periodeDari = 80 + $bulan;
      $periodeSampai = 80 + $bulan;
    } else {
      $periodeDari = 81;
      $periodeSampai = 80 + $bulanPotong;
    }

    $buatSubJadwal = fn() => BosRapbsPeriode::query()
      ->select('sekolah_id', 'id_rapbs_arkas')
      ->selectRaw('SUM(jumlah) as jadwal')
      ->where('soft_delete', false)
      ->whereBetween('id_periode', [$periodeDari, $periodeSampai])
      ->groupBy('sekolah_id', 'id_rapbs_arkas');

    $buatSubRealisasi = fn() => BosKasUmum::query()
      ->from('bos_kas_umum as bku16')
      ->join('bos_rapbs_periode as per16', function ($j) {
        $j->on('per16.id_arkas', '=', 'bku16.id_rapbs_periode')
          ->on('per16.sekolah_id', '=', 'bku16.sekolah_id');
      })
      ->select('per16.sekolah_id', 'per16.id_rapbs_arkas')
      ->selectRaw($tampilan === 'rencana'
        ? 'SUM(CASE WHEN per16.id_periode = ' . $periodeSampai . ' THEN bku16.saldo ELSE 0 END) as realisasi, MAX(CASE WHEN per16.id_periode = ' . $periodeSampai . ' THEN bku16.tanggal_transaksi END) as realisasi_terakhir'
        : 'SUM(bku16.saldo) as realisasi, MAX(bku16.tanggal_transaksi) as realisasi_terakhir')
      ->where('bku16.soft_delete', false)
      ->whereIn('bku16.id_ref_bku', self::JENIS_BELANJA)
      ->whereNotNull('bku16.id_rapbs_periode')
      ->groupBy('per16.sekolah_id', 'per16.id_rapbs_arkas');

    // ── Jadwal tertua yang belum lunas (Fase 1.8) ──
    // Untuk tampilan belanja: bulan jadwal paling awal
    // (Januari s.d. bulan potong) yang realisasi per baris
    // periodenya masih kurang dari jumlah jadwal baris
    // periode itu. Realisasi per baris periode dihitung
    // dulu sebagai subquery agar join tidak menggandakan
    // baris periode.
    $realisasiPerPeriode = BosKasUmum::query()
      ->select('sekolah_id', 'id_rapbs_periode')
      ->selectRaw('SUM(saldo) as real_baris')
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->whereNotNull('id_rapbs_periode')
      ->groupBy('sekolah_id', 'id_rapbs_periode');

    $buatSubTertua = fn() => BosRapbsPeriode::query()
      ->from('bos_rapbs_periode as perT')
      ->leftJoinSub($realisasiPerPeriode, 'reaT', function ($j) {
        $j->on('reaT.id_rapbs_periode', '=', 'perT.id_arkas')
          ->on('reaT.sekolah_id', '=', 'perT.sekolah_id');
      })
      ->select('perT.sekolah_id', 'perT.id_rapbs_arkas')
      ->selectRaw('MIN(CASE WHEN COALESCE(reaT.real_baris, 0) < perT.jumlah THEN perT.id_periode END) as tertua_periode')
      ->where('perT.soft_delete', false)
      ->whereBetween('perT.id_periode', [81, 80 + $bulanPotong])
      ->groupBy('perT.sekolah_id', 'perT.id_rapbs_arkas');

    $denganAgg = function ($q) use ($buatSubJadwal, $buatSubRealisasi, $buatSubTertua, $tampilan) {
      $q = $q
        ->leftJoinSub($buatSubJadwal(), 'jad', function ($j) {
          $j->on('jad.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('jad.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        })
        ->leftJoinSub($buatSubRealisasi(), 'rea', function ($j) {
          $j->on('rea.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('rea.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        });
      if ($tampilan === 'belanja') {
        $q = $q->leftJoinSub($buatSubTertua(), 'ter', function ($j) {
          $j->on('ter.id_rapbs_arkas', '=', 'bos_rapbs.id_arkas')
            ->on('ter.sekolah_id', '=', 'bos_rapbs.sekolah_id');
        });
      }
      return $q;
    };

    // Kelas status per baris dari jadwal & realisasi.
    $kasusStatus = "CASE
      WHEN COALESCE(jad.jadwal, 0) > 0 AND COALESCE(rea.realisasi, 0) >= COALESCE(jad.jadwal, 0) THEN 'sudah'
      WHEN COALESCE(rea.realisasi, 0) > 0 AND COALESCE(rea.realisasi, 0) < COALESCE(jad.jadwal, 0) THEN 'sebagian'
      WHEN COALESCE(rea.realisasi, 0) = 0 AND COALESCE(jad.jadwal, 0) > 0 THEN 'belum'
      ELSE 'belum_terjadwal' END";

    $totalJumlah = (float) $saring($dasar(), $idsUtama)->sum('bos_rapbs.jumlah');

    // Kartu ringkasan jadwal/realisasi: cakupan sama dengan
    // tabel (versi, kegiatan, pencarian) tetapi TANPA
    // saringan status — status adalah saringan tampilan
    // atas kolom hitungan ini, bukan cakupan datanya.
    // Pengecualian Fase 1.8: tampilan belanja memang
    // DIDEFINISIKAN oleh saringannya (hanya baris bersisa),
    // jadi kartu mengikuti himpunan yang tampil — kartu
    // "Bisa Dibelanjakan" adalah sisa himpunan itu. Pada
    // tampilan rencana kartu hanya menjumlah baris yang
    // punya jadwal di bulan pilihan (total rencana bulan
    // itu serta realisasinya).
    $saringBelanja = 'COALESCE(jad.jadwal, 0) > 0 AND COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) > 0';
    $aggKartu = $denganAgg($saring($dasar(), $idsUtama))
      ->when($tampilan === 'belanja', fn($x) => $x->whereRaw("({$saringBelanja})"))
      ->when($tampilan === 'rencana', fn($x) => $x->whereNotNull('jad.jadwal'))
      ->selectRaw('COALESCE(SUM(jad.jadwal), 0) as terjadwal, COALESCE(SUM(rea.realisasi), 0) as realisasi')
      ->first();
    $kartu = [
      'terjadwal' => (float) ($aggKartu->terjadwal ?? 0),
      'realisasi' => (float) ($aggKartu->realisasi ?? 0),
    ];
    $kartu['sisa'] = $kartu['terjadwal'] - $kartu['realisasi'];

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
      'jadwal' => 'jadwal',
      'realisasi' => 'realisasi',
      'sisa' => 'sisa',
      'tertua' => 'tertua_periode',
    ];
    $urut = $kolomUrut[$request->input('urut')] ?? null;
    $urutKunci = $urut ? $request->input('urut') : 'kegiatan';
    $arah = $this->arah($request);

    $baris = $denganAgg($saring($dasar(), $idsUtama))
      ->when($tampilan === 'belanja', fn($x) => $x->whereRaw("({$saringBelanja})"))
      ->when($tampilan === 'rencana', fn($x) => $x->whereNotNull('jad.jadwal'))
      ->when($statusFilter !== '', fn($x) => $x->whereRaw("({$kasusStatus}) = ?", [$statusFilter]))
      ->select([
        'bos_rapbs.*',
        'bos_anggaran.tahun_anggaran',
        'bos_anggaran.nama_sumber_dana',
        'bos_ref_kode.id_kode as kode_kegiatan',
        'bos_ref_kode.uraian_kode as nama_kegiatan',
      ])
      ->selectRaw('bos_ref_rekening.nama_rekening as nama_rekening')
      ->selectRaw('COALESCE(jad.jadwal, 0) as jadwal, COALESCE(rea.realisasi, 0) as realisasi')
      ->selectRaw('rea.realisasi_terakhir as realisasi_terakhir')
      ->selectRaw('COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) as sisa')
      ->selectRaw($kasusStatus . ' as status_kelas')
      ->when($tampilan === 'belanja', fn($x) => $x->selectRaw('ter.tertua_periode as tertua_periode'))
      ->when(
        $tampilan === 'belanja' && !$urut,
        fn($x) => $x->orderBy('tertua_periode', 'asc')->orderBy('bos_rapbs.uraian', 'asc'),
        fn($x) => $x->orderBy($urut ?? 'bos_ref_kode.uraian_kode', $urut ? $arah : 'asc')
      )
      ->orderBy('bos_rapbs.kode_rekening')
      ->orderBy('bos_rapbs.urutan')
      ->paginate(25)
      ->withQueryString();

    $daftarKegiatan = BosRefKode::query()
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
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunReq,
      'sumberTerpilih' => $sumberReq,
      'daftarSumberDana' => $this->daftarSumberDana($sekolahId),
      'daftarKegiatan' => $daftarKegiatan,
      'daftarVersi' => $this->pilihanVersi($kelompok, $tahunReq, $sumberReq),
      'daftarBulan' => self::NAMA_BULAN,
      'bulan' => $bulan,
      'bulanPotong' => $bulanPotong,
      'tahunAcuan' => $tahunAcuan,
      'tampilan' => $tampilan,
      'totalRencana' => $tampilan === 'rencana' ? $kartu['terjadwal'] : 0,
      'statusFilter' => $statusFilter,
      'kartu' => $kartu,
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => $this->petaAlias($sekolahId, $baris->getCollection()->pluck('uraian')),
      'bisaTulisAlias' => $this->bisaTulisAlias(),
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
      'tahunTerpilih' => null,
      'sumberTerpilih' => null,
      'versiATerpilih' => '',
      'versiBTerpilih' => '',
    ];
    if (!$sekolahId) {
      return view('content.kelola-bos.perbandingan', $konteks + $kosong);
    }

    // Bawaan Fase 1.10 sama seperti Kertas Kerja: tahun
    // berjalan & BOS Reguler bila parameternya tidak
    // dikirim sama sekali.
    $tahunReq = $this->tahunEfektif($request, $kosong['daftarTahun']);
    $kelompok = $this->kelompokVersi($sekolahId);
    $sumberReq = $request->filled('sumber_dana')
      ? $request->integer('sumber_dana')
      : ($request->has('sumber_dana') ? null : $this->sumberBawaan($kelompok, $tahunReq));
    $kosong['daftarVersi'] = $this->pilihanVersi($kelompok, $tahunReq, $sumberReq);
    $kosong['tahunTerpilih'] = $tahunReq;
    $kosong['sumberTerpilih'] = $sumberReq;

    // Bawaan versi (Fase 1.10): A = versi disetujui
    // terakhir pada (tahun, sumber) efektif; B = versi
    // terbaru yang berbeda dari A — memunculkan draf
    // yang sedang dikerjakan (mis. Revisi 3) — cadangan
    // versi sebelum A, terakhir A itu sendiri. Bawaan
    // hanya bila parameternya tidak dikirim sama sekali.
    $grupScope = null;
    foreach ($kelompok as $k) {
      if (($tahunReq === null || $k['tahun'] === $tahunReq)
        && ($sumberReq === null || $k['sumber'] === $sumberReq)) {
        $grupScope = $k;
        break;
      }
    }
    if ($request->has('versi_a')) {
      $idA = (string) $request->input('versi_a', '');
    } else {
      $idA = '';
      if ($grupScope) {
        $disetujui = $grupScope['versi']->filter(fn($a) => (int) $a->is_approve === 1)->last();
        $pilihA = $disetujui ?: $grupScope['versi']->last();
        $idA = $pilihA ? (string) $pilihA->id_arkas : '';
      }
    }
    if ($request->has('versi_b')) {
      $idB = (string) $request->input('versi_b', '');
    } else {
      // Kelompok pemilik A (bisa berbeda dari kelompok
      // cakupan bila A dipilih eksplisit).
      $grupB = $grupScope;
      foreach ($kelompok as $k) {
        if ($k['versi']->contains(fn($a) => (string) $a->id_arkas === $idA)) {
          $grupB = $k;
          break;
        }
      }
      $idB = '';
      if ($grupB) {
        $daftar = $grupB['versi'];
        $terbaru = $daftar->last();
        if ($terbaru && (string) $terbaru->id_arkas !== $idA) {
          $idB = (string) $terbaru->id_arkas;
        } else {
          $idx = $daftar->search(fn($a) => (string) $a->id_arkas === $idA);
          $idB = ($idx !== false && $idx > 0)
            ? (string) $daftar[$idx - 1]->id_arkas
            : $idA;
        }
      }
    }
    $kosong['versiATerpilih'] = $idA;
    $kosong['versiBTerpilih'] = $idB;
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
    $petaKegiatan = BosRefKode::where('tahun', $tahunBanding)
      ->whereNotNull('id_ref_kode')
      ->select('id_ref_kode')
      ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
      ->groupBy('id_ref_kode')
      ->get()
      ->keyBy('id_ref_kode');
    $petaRekening = BosRefRekening::where('tahun', $tahunBanding)
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
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'tahunTerpilih' => null,
        'terbatasRekening' => false,
        'bisaDibatasiRekening' => false,
      ]);
    }

    $kolomUrut = ['nama' => 'nama_barang', 'harga' => 'harga_barang'];
    $urutReq = $request->input('urut');
    $urutKunci = isset($kolomUrut[$urutReq]) ? $urutReq : 'nama';
    $arah = $this->arah($request);

    $daftarTahun = BosRefAcuanBarang::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan rekening (Fase 1.11): secara bawaan
    // hanya barang pada rekening yang pernah dipakai
    // kertas kerja sekolah ini yang tampil — katalog
    // SSH nasional memuat barang semua sektor (sapi,
    // perahu, bioflok) yang tidak pernah bisa
    // dibelanjakan sekolah. ?semua=1 menonaktifkannya.
    $rekeningTerpakai = $this->rekeningTerpakai($sekolahId);
    $terbatasRekening = $rekeningTerpakai !== [] && $request->input('semua') !== '1';

    $baris = BosRefAcuanBarang::query()
      ->when($terbatasRekening, fn($x) => $x->whereIn('kode_rekening', $rekeningTerpakai))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
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
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'daftarBlok' => BosRefAcuanBarang::query()
        ->distinct()->orderBy('blok_id')->pluck('blok_id')->all(),
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => [],
      'bisaTulisAlias' => $this->bisaTulisAlias(),
      'terbatasRekening' => $terbatasRekening,
      'bisaDibatasiRekening' => $rekeningTerpakai !== [],
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
        'tahunTerpilih' => null,
        'bentukSekolah' => null,
        'terbatasJenjang' => false,
        'terbatasLingkup' => false,
        'bisaDibatasi' => false,
      ]);
    }

    $daftarTahun = BosRefKode::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan jenjang (Fase 1.10) + sumber dana
    // (Fase 1.11): secara bawaan hanya varian bentuk
    // pendidikan sekolah ini DAN sumber dana yang
    // dimiliki sekolah ini (dari anggarannya) yang
    // tampil — kode milik jenjang lain, program Banpem
    // khusus, atau sumber dana yang tidak dimiliki
    // sekolah tidak lagi mengotori daftar. ?semua=1
    // menonaktifkan keduanya (?jenjang=semua tetap
    // diterima sebagai aliasnya). Cermin sendiri tetap
    // lengkap; ini murni saringan tampil.
    $bentuk = $this->bentukSekolah($sekolahId);
    $sumberSet = $this->sumberSekolah($sekolahId, $tahunEfektif);
    $tampilkanSemua = $request->input('semua') === '1' || $request->input('jenjang') === 'semua';
    $terbatasJenjang = $bentuk !== null && !$tampilkanSemua;
    $terbatasSumber = $sumberSet !== [] && !$tampilkanSemua;

    // ref_kode tersimpan per varian (sumber dana/bentuk);
    // untuk referensi cukup tampilkan baris unik per
    // (id_kode, uraian, level, tahun). Saringan bentuk
    // & sumber diterapkan SEBELUM pengelompokan agar
    // kelompok menyusut ke varian sekolah ini.
    $baris = BosRefKode::query()
      ->whereNotNull('id_kode')
      ->when($terbatasJenjang, fn($x) => $x->where('bentuk_pendidikan_id', $bentuk))
      ->when($terbatasSumber, fn($x) => $x->whereIn('sumber_dana_id', $sumberSet))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
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
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'bentukSekolah' => $bentuk,
      'terbatasJenjang' => $terbatasJenjang,
      'terbatasLingkup' => $terbatasJenjang || $terbatasSumber,
      'bisaDibatasi' => $bentuk !== null || $sumberSet !== [],
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
        'tahunTerpilih' => null,
        'terbatasRekening' => false,
        'bisaDibatasiRekening' => false,
      ]);
    }

    $daftarTahun = BosRefRekening::query()
      ->distinct()->orderByDesc('tahun')->pluck('tahun')->map(fn($t) => (int) $t)->all();
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    // Pembatasan rekening (Fase 1.11): sama seperti
    // Referensi Barang — secara bawaan hanya rekening
    // yang pernah dipakai kertas kerja sekolah ini.
    // ?semua=1 menonaktifkannya.
    $rekeningTerpakai = $this->rekeningTerpakai($sekolahId);
    $terbatasRekening = $rekeningTerpakai !== [] && $request->input('semua') !== '1';

    $baris = BosRefRekening::query()
      ->when($terbatasRekening, fn($x) => $x->whereIn('kode_rekening', $rekeningTerpakai))
      ->when($tahunEfektif !== null, fn($x) => $x->where('tahun', $tahunEfektif))
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
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'terbatasRekening' => $terbatasRekening,
      'bisaDibatasiRekening' => $rekeningTerpakai !== [],
    ]);
  }

  // ── Kartu dana & anggaran (halaman BKU) ─────────────────

  /**
   * Ringkasan dana dari jumlah saldo per jenis BKU
   * ($perJenis: [id_ref_bku => ['jml' => int, 'total' => float]]) yang
   * sudah dibatasi pada saldo awal terakhir + transaksi sesudahnya.
   * Dana tersedia = tunai + non tunai (bank). Jenis di luar pemetaan
   * tidak ikut dihitung dan dilaporkan lewat 'takDikenal'.
   */
  private function ringkasDana(array $perJenis, float $pajak): array
  {
    $jumlahkan = function (array $peta) use ($perJenis): float {
      $hasil = 0.0;
      foreach ($peta as $idJenis => $tanda) {
        $hasil += $tanda * ($perJenis[$idJenis]['total'] ?? 0.0);
      }
      return $hasil;
    };

    $tunai = $jumlahkan(self::DANA_TUNAI);
    $bank = $jumlahkan(self::DANA_BANK);

    $dikenal = array_merge(array_keys(self::DANA_TUNAI), array_keys(self::DANA_BANK), self::DANA_DIABAIKAN);
    $takDikenal = ['jml' => 0, 'ids' => []];
    foreach ($perJenis as $idJenis => $d) {
      if (!in_array($idJenis, $dikenal, true) && $d['jml'] > 0) {
        $takDikenal['jml'] += $d['jml'];
        $takDikenal['ids'][] = $idJenis;
      }
    }

    return [
      'tersedia' => $tunai + $bank,
      'tunai' => $tunai,
      'bank' => $bank,
      'pajak' => $pajak,
      'takDikenal' => $takDikenal,
    ];
  }

  /**
   * Kartu dana: posisi pada akhir bulan $bulanSampai tahun $tahun (atau
   * akhir tahun bila bulan tidak dipilih). Tidak ikut saringan jenis,
   * rekening, atau pencarian — ini posisi, bukan daftar.
   *
   * Cara hitung: untuk TIAP anggaran (sumber dana punya rantai saldo
   * sendiri), ambil baris Saldo Awal Bank/Tunai terakhir sebelum batas,
   * lalu jumlahkan baris itu dan seluruh transaksi sesudahnya sampai
   * batas. Saldo awal ARKAS adalah saldo penutup bulan sebelumnya, jadi
   * hasilnya sama dengan saldo penutup bulan yang bersangkutan.
   *
   * Pajak: jumlah Pajak Belanja Terima sejak 1 Januari sampai batas,
   * dikurangi pajak yang melekat pada nota beli di SIPLah (pajaknya
   * diselesaikan marketplace, tidak dipegang sekolah).
   */
  private function hitungKartuDana(int $sekolahId, ?int $tahun, ?int $bulanSampai): array
  {
    $bulanSampai = ($bulanSampai !== null && $bulanSampai >= 1 && $bulanSampai <= 12) ? $bulanSampai : null;
    $perJenis = [];
    $pajak = 0.0;

    if ($tahun !== null) {
      $awalTahun = \Carbon\Carbon::create($tahun, 1, 1)->startOfDay();
      $sebelum = \Carbon\Carbon::create($tahun, $bulanSampai ?? 12, 1)->addMonth()->startOfMonth();
      $batas = $sebelum->format('Y-m-d H:i:s');

      $sql = <<<SQL
        SELECT k.id_ref_bku, COUNT(*) AS jml, SUM(k.saldo) AS total
        FROM bos_kas_umum k
        WHERE k.sekolah_id = ?
          AND k.soft_delete = 0
          AND k.tanggal_transaksi < ?
          AND k.tanggal_transaksi >= COALESCE((
            SELECT MAX(s.tanggal_transaksi)
            FROM bos_kas_umum s
            WHERE s.sekolah_id = k.sekolah_id
              AND s.soft_delete = 0
              AND s.id_ref_bku IN (8, 9)
              AND s.id_anggaran_arkas = k.id_anggaran_arkas
              AND s.tanggal_transaksi < ?
          ), '1900-01-01 00:00:00')
        GROUP BY k.id_ref_bku
        SQL;

      foreach (\Illuminate\Support\Facades\DB::select($sql, [$sekolahId, $batas, $batas]) as $b) {
        $perJenis[(int) $b->id_ref_bku] = ['jml' => (int) $b->jml, 'total' => (float) $b->total];
      }

      $pajak = (float) BosKasUmum::query()
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_ref_bku', self::JENIS_PAJAK)
        ->where('tanggal_transaksi', '>=', $awalTahun)
        ->where('tanggal_transaksi', '<', $sebelum)
        ->sum('saldo');

      // Pajak belanja SIPLah tidak dipegang sekolah
      // (diselesaikan marketplace lewat pembayaran VA),
      // jadi dikeluarkan dari kartu jumlah pajak: baris
      // pajak yang notanya bertanda beli di SIPLah.
      $pajakSiplah = (float) BosKasUmum::query()
        ->join('bos_kas_nota', function ($j) {
          $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
            ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
        })
        ->where('bos_kas_umum.sekolah_id', $sekolahId)
        ->where('bos_kas_umum.soft_delete', false)
        ->where('bos_kas_umum.id_ref_bku', self::JENIS_PAJAK)
        ->where('bos_kas_umum.tanggal_transaksi', '>=', $awalTahun)
        ->where('bos_kas_umum.tanggal_transaksi', '<', $sebelum)
        ->where('bos_kas_nota.soft_delete', false)
        ->where('bos_kas_nota.is_beli_di_siplah', true)
        ->sum('bos_kas_umum.saldo');

      $pajak = max(0.0, $pajak - $pajakSiplah);
    }

    return ['tahun' => $tahun, 'bulan' => $bulanSampai] + $this->ringkasDana($perJenis, $pajak);
  }

  /**
   * Kartu anggaran s.d. bulan berjalan — definisi yang sama dengan
   * Kertas Kerja (titik potong bulan otomatis, versi RKAS disetujui
   * terakhir), tetapi mencakup semua sumber dana karena halaman BKU
   * tidak per sumber dana.
   *  - bisa      : jumlah sisa jadwal baris yang masih bersisa
   *                (kartu "Bisa Dibelanjakan" Kertas Kerja)
   *  - sudah     : realisasi BKU belanja baris-baris itu
   *  - bulanIni  : jadwal bulan titik potong saja
   *  - terjadwal : jadwal kumulatif Januari s.d. bulan titik potong
   */
  private function hitungKartuAnggaran(int $sekolahId, ?int $tahun, ?int $bulanPilih = null): array
  {
    $tahunIni = (int) now()->format('Y');
    $tahunAcuan = $tahun ?? $tahunIni;
    // Bulan terpilih di saringan menjadi titik potong (sama
    // seperti kartu dana); tanpa pilihan, titik potong mengikuti
    // kalender: bulan berjalan untuk tahun ini, Desember untuk
    // tahun lampau, kosong untuk tahun depan.
    $bulanPotong = $bulanPilih !== null
      ? max(1, min(12, $bulanPilih))
      : ($tahunAcuan === $tahunIni
        ? (int) now()->format('n')
        : ($tahunAcuan < $tahunIni ? 12 : 0));

    $hasil = [
      'tahun' => $tahunAcuan,
      'bulan' => $bulanPotong,
      'bulanDipilih' => $bulanPilih !== null,
      'ada' => false,
      'terjadwal' => 0.0,
      'bisa' => 0.0,
      'sudah' => 0.0,
      'bulanIni' => 0.0,
    ];

    if ($bulanPotong < 1) {
      return $hasil;
    }

    $ids = $this->pilihVersiIds($this->kelompokVersi($sekolahId), $tahunAcuan, null, null);
    if (!$ids) {
      return $hasil;
    }

    $periodeIni = 80 + $bulanPotong; // id_periode 81–92 = Januari–Desember
    $jenisBelanja = implode(',', array_map('intval', self::JENIS_BELANJA));
    $tandaTanya = implode(',', array_fill(0, count($ids), '?'));

    $sql = <<<SQL
      SELECT
        COALESCE(SUM(jad.jadwal), 0) AS terjadwal,
        COALESCE(SUM(jad.bulan_ini), 0) AS bulan_ini,
        COALESCE(SUM(rea.realisasi), 0) AS sudah,
        COALESCE(SUM(CASE
          WHEN COALESCE(jad.jadwal, 0) > 0 AND COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0) > 0
          THEN COALESCE(jad.jadwal, 0) - COALESCE(rea.realisasi, 0)
          ELSE 0 END), 0) AS bisa
      FROM bos_rapbs
      LEFT JOIN (
        SELECT sekolah_id, id_rapbs_arkas,
               SUM(jumlah) AS jadwal,
               SUM(CASE WHEN id_periode = {$periodeIni} THEN jumlah ELSE 0 END) AS bulan_ini
        FROM bos_rapbs_periode
        WHERE soft_delete = 0 AND id_periode BETWEEN 81 AND {$periodeIni}
        GROUP BY sekolah_id, id_rapbs_arkas
      ) jad ON jad.id_rapbs_arkas = bos_rapbs.id_arkas AND jad.sekolah_id = bos_rapbs.sekolah_id
      LEFT JOIN (
        SELECT per16.sekolah_id, per16.id_rapbs_arkas, SUM(bku16.saldo) AS realisasi
        FROM bos_kas_umum bku16
        JOIN bos_rapbs_periode per16
          ON per16.id_arkas = bku16.id_rapbs_periode AND per16.sekolah_id = bku16.sekolah_id
        WHERE bku16.soft_delete = 0
          AND bku16.id_ref_bku IN ({$jenisBelanja})
          AND bku16.id_rapbs_periode IS NOT NULL
        GROUP BY per16.sekolah_id, per16.id_rapbs_arkas
      ) rea ON rea.id_rapbs_arkas = bos_rapbs.id_arkas AND rea.sekolah_id = bos_rapbs.sekolah_id
      WHERE bos_rapbs.sekolah_id = ?
        AND bos_rapbs.soft_delete = 0
        AND bos_rapbs.id_anggaran_arkas IN ({$tandaTanya})
      SQL;

    $agg = \Illuminate\Support\Facades\DB::selectOne($sql, array_merge([$sekolahId], $ids));

    return [
      'ada' => true,
      'terjadwal' => (float) ($agg->terjadwal ?? 0),
      'bisa' => (float) ($agg->bisa ?? 0),
      'sudah' => (float) ($agg->sudah ?? 0),
      'bulanIni' => (float) ($agg->bulan_ini ?? 0),
    ] + $hasil;
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
        'totalAnggaran' => 0,
        'tahunAnggaran' => (int) now()->format('Y'),
        'sumberTerpilih' => null,
        'namaSumberTerpilih' => null,
        'daftarSumberDana' => collect(),
        'daftarTahun' => [],
        'daftarJenis' => collect(),
        'daftarRekening' => collect(),
        'urut' => 'tanggal',
        'arah' => 'desc',
        'petaAlias' => [],
        'bisaTulisAlias' => false,
        'bisaTulisPenerima' => false,
        'jumlahPenerima' => [],
        'namaPenerima' => [],
        'metaGrup' => [],
        'kunciPerBaris' => [],
        'barisTampil' => [],
        'pajakGrup' => [],
        'tahunTerpilih' => null,
      ]);
    }

    $dasar = fn() => BosKasUmum::query()
      ->from('bos_kas_umum')
      ->leftJoin('bos_ref_bku', function ($j) {
        $j->on('bos_ref_bku.id_ref_bku', '=', 'bos_kas_umum.id_ref_bku');
      })
      ->leftJoin('bos_kas_nota', function ($j) {
        $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
          ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->where('bos_kas_umum.sekolah_id', $sekolahId)
      ->where('bos_kas_umum.soft_delete', false);

    // Bawaan tahun (Fase 1.10): tahun berjalan bila ada
    // transaksi tahun itu, selain itu tahun terbesar.
    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
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
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    $saring = function ($q) use ($request, $tahunEfektif) {
      $q->when($tahunEfektif !== null, fn($x) => $x->whereYear('bos_kas_umum.tanggal_transaksi', $tahunEfektif))
        ->when($request->filled('bulan'), fn($x) => $x->whereMonth('bos_kas_umum.tanggal_transaksi', $request->integer('bulan')))
        ->when($request->filled('jenis'), fn($x) => $x->where('bos_kas_umum.id_ref_bku', $request->integer('jenis')))
        // Bawaan (tanpa saringan jenis eksplisit): tabel
        // hanya menampilkan transaksi belanja — baris lain
        // (terima dana, saldo awal, pajak, tarik tunai, ...)
        // mengaburkan daftar kerja SPJ. Memilih jenis lain
        // secara eksplisit tetap menampilkannya.
        ->when(!$request->filled('jenis'), fn($x) => $x->whereIn('bos_kas_umum.id_ref_bku', self::JENIS_BELANJA))
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

    // Angka kepala halaman: total anggaran tahun berjalan
    // (tahun efektif halaman; "semua tahun" = tahun kalender
    // berjalan) untuk sumber dana terpilih — menggantikan
    // total nominal saringan yang menjumlah semua jenis BKU
    // sekaligus sehingga uang yang sama terhitung berulang.
    // Pilihan sumber mengikuti pola Kertas Kerja: parameter
    // tidak dikirim = bawaan (BOS Reguler bila ada); dikirim
    // kosong secara eksplisit = semua sumber dana. Totalnya
    // jumlah baris RAPBS versi disetujui terakhir, definisi
    // yang sama dengan ringkasan Kertas Kerja.
    $tahunAnggaran = $tahunEfektif ?? (int) now()->format('Y');
    $kelompok = $this->kelompokVersi($sekolahId);
    $sumberTerpilih = $request->filled('sumber_dana')
      ? $request->integer('sumber_dana')
      : ($request->has('sumber_dana') ? null : $this->sumberBawaan($kelompok, $tahunAnggaran));
    $idsAnggaran = $this->pilihVersiIds($kelompok, $tahunAnggaran, $sumberTerpilih, null);
    $totalAnggaran = $idsAnggaran
      ? (float) BosRapbs::where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->whereIn('id_anggaran_arkas', $idsAnggaran)
        ->sum('jumlah')
      : 0.0;
    $namaSumberTerpilih = null;
    if ($sumberTerpilih !== null) {
      foreach ($kelompok as $k) {
        if ($k['tahun'] === $tahunAnggaran && $k['sumber'] === $sumberTerpilih) {
          $namaSumberTerpilih = $k['nama_sumber'];
          break;
        }
      }
      $namaSumberTerpilih ??= 'Sumber dana #' . $sumberTerpilih;
    }

    // Kartu dana & anggaran di atas tabel (tidak ikut saringan
    // jenis/rekening/pencarian). Saat "Semua tahun" dipilih, kartu dana
    // memakai tahun terbaru karena saldo awal dicatat per tahun.
    $tahunDana = $tahunEfektif ?? ($daftarTahun[0] ?? null);
    $kartuDana = $this->hitungKartuDana(
      $sekolahId,
      $tahunDana,
      $request->filled('bulan') ? $request->integer('bulan') : null
    );
    $kartuAnggaran = $this->hitungKartuAnggaran(
      $sekolahId,
      $tahunEfektif,
      $request->filled('bulan') ? $request->integer('bulan') : null
    );

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
    $tahunTransaksi = $driver === 'mysql'
      ? 'YEAR(bos_kas_umum.tanggal_transaksi)'
      : "CAST(strftime('%Y', bos_kas_umum.tanggal_transaksi) AS INTEGER)";

    $baris = $saring($dasar())
      ->select([
        'bos_kas_umum.*',
        'bos_ref_bku.nama_bku',
        'bos_ref_bku.kode_bku',
        'bos_kas_nota.nama_toko',
        // Penanda SIPLah per baris untuk lencana di kolom
        // Toko/Penerima — diambil dari join nota yang sudah
        // ada (relasi nota() tidak bisa di-eager-load di
        // sini: ia menyaring sekolah dari $this model).
        'bos_kas_nota.is_beli_di_siplah',
      ])
      ->selectRaw("(SELECT nama_rekening FROM bos_ref_rekening r
        WHERE r.kode_rekening = bos_kas_umum.kode_rekening
          AND r.tahun = {$tahunTransaksi}
        LIMIT 1) as nama_rekening")
      ->orderBy($urut ?? 'bos_kas_umum.tanggal_transaksi', $urut ? $arah : 'desc')
      ->orderByDesc('bos_kas_umum.id')
      ->paginate(25)
      ->withQueryString();

    $daftarJenis = BosRefBku::query()
      ->whereIn('id_ref_bku', function ($q) use ($sekolahId) {
        $q->select('id_ref_bku')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)->distinct();
      })
      ->orderBy('nama_bku')
      ->get();

    $daftarRekening = BosRefRekening::query()
      ->whereIn('kode_rekening', function ($q) use ($sekolahId) {
        $q->select('kode_rekening')->from('bos_kas_umum')
          ->where('sekolah_id', $sekolahId)
          ->whereNotNull('kode_rekening')->distinct();
      })
      ->select('kode_rekening', 'nama_rekening')
      ->distinct()
      ->orderBy('kode_rekening')
      ->get();

    // Jumlah penerima SPJ (Fase 2) per baris belanja di
    // halaman ini — satu query hitung berkelompok menurut
    // kunci grup kwitansi, lalu dipetakan ke id baris.
    $jumlahPenerima = [];
    $kunciPerBaris = [];
    // Keanggotaan grup manual (gabung/pecah nota) menimpa
    // pengelompokan bawaan ARKAS — peta dibangun sekali
    // untuk seluruh baris halaman ini.
    $petaManual = $this->petaGrupManual($sekolahId, $baris->getCollection()->pluck('id')->all());
    foreach ($baris as $b) {
      if (in_array((int) $b->id_ref_bku, self::JENIS_BELANJA, true)) {
        $kunciPerBaris[$b->id] = $this->kunciGrupKwitansi($b, $petaManual);
      }
    }
    if ($kunciPerBaris) {
      $hitung = BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', array_unique(array_values($kunciPerBaris)))
        ->selectRaw('grup_kunci, COUNT(*) as jml')
        ->groupBy('grup_kunci')
        ->pluck('jml', 'grup_kunci');
      foreach ($kunciPerBaris as $barisId => $kunci) {
        $jumlahPenerima[$barisId] = (int) ($hitung[$kunci] ?? 0);
      }
    }

    // Nama penerima SPJ per baris belanja — pola yang
    // sama: satu query menurut kunci grup, nama
    // dikelompokkan per grup lalu dipetakan ke id
    // baris. Ditampilkan di kolom Toko/Penerima
    // menggantikan nama toko bila grup sudah punya
    // penerima. Baris tanpa penerima tidak masuk peta.
    $namaPenerima = [];
    if ($kunciPerBaris) {
      $daftarNama = BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', array_unique(array_values($kunciPerBaris)))
        ->orderBy('urutan')
        ->orderBy('id')
        ->get(['grup_kunci', 'nama']);
      $namaPerGrup = [];
      foreach ($daftarNama as $p) {
        $namaPerGrup[$p->grup_kunci][] = $p->nama;
      }
      foreach ($kunciPerBaris as $barisId => $kunci) {
        if (!empty($namaPerGrup[$kunci])) {
          $namaPenerima[$barisId] = $namaPerGrup[$kunci];
        }
      }
    }

    // Statistik penerima per kunci grup (jumlah total &
    // jumlah yang sudah selesai) untuk status tampil —
    // satu query teragregasi atas kunci-kunci halaman
    // ini, pola yang sama dengan pemuatan nama penerima
    // di atas.
    $statistikPenerima = [];
    $kunciHalaman = collect($kunciPerBaris)->unique()->values();
    if ($kunciHalaman->isNotEmpty()) {
      $statistikPenerima = BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', $kunciHalaman)
        ->selectRaw('grup_kunci, COUNT(*) AS jumlah, SUM(CASE WHEN selesai THEN 1 ELSE 0 END) AS jumlah_selesai, SUM(CASE WHEN jenis = \'orang\' THEN 1 ELSE 0 END) AS jumlah_orang')
        ->groupBy('grup_kunci')
        ->get()
        ->mapWithKeys(fn($r) => [$r->grup_kunci => [
          'jumlah' => (int) $r->jumlah,
          'jumlah_selesai' => (int) $r->jumlah_selesai,
          'jumlah_orang' => (int) $r->jumlah_orang,
        ]])
        ->all();
    }

    // Meta grup SPJ (status, nomor VA, kategori) untuk
    // lencana di kolom Toko/Penerima — pola yang sama:
    // satu query menurut kunci grup, dipetakan ke id
    // baris. SETIAP baris belanja kini mendapat entri:
    // baris tanpa meta memakai bawaan status 'belum' &
    // VA kosong, dan kategori efektif = override manual
    // (kolom kategori) bila terisi, selain itu hasil
    // deteksi dari kode rekening baris-baris grup yang
    // tampil di halaman ini.
    // Status tampil: grup berpenerima diturunkan dari
    // penanda selesai penerimanya (lihat
    // statusTampilGrup), grup tanpa penerima mengikuti
    // status meta.
    $metaGrup = [];
    if ($kunciPerBaris) {
      $daftarMeta = BosSpjGrup::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', array_unique(array_values($kunciPerBaris)))
        ->get()
        ->keyBy('grup_kunci');
      $grupPerKunci = [];
      foreach ($baris as $b) {
        $kunciBaris = $kunciPerBaris[$b->id] ?? null;
        if ($kunciBaris !== null) {
          $grupPerKunci[$kunciBaris][] = $b;
        }
      }
      foreach ($kunciPerBaris as $barisId => $kunci) {
        $meta = $daftarMeta->get($kunci);
        $stat = $statistikPenerima[$kunci] ?? null;
        if ($stat && $stat['jumlah'] > 0) {
          $statusTampil = $stat['jumlah_selesai'] >= $stat['jumlah']
            ? 'selesai'
            : ($stat['jumlah_selesai'] > 0 ? 'sebagian' : 'belum');
        } else {
          $statusTampil = ($meta->status ?? 'belum') === 'selesai' ? 'selesai' : 'belum';
        }
        $metaGrup[$barisId] = [
          'status' => $meta->status ?? 'belum',
          'status_tampil' => $statusTampil,
          'nama' => $meta->nama ?? null,
          'nomor_va' => $meta->nomor_va ?? null,
          'kategori' => $this->kategoriGrup($grupPerKunci[$kunci] ?? [], $meta)['efektif'],
          'jumlah_penerima_orang' => (int) ($stat['jumlah_orang'] ?? 0),
        ];
      }
    }

    // Daftar tampil tabel: baris belanja yang satu grup SPJ
    // (per nota / nomor bukti, atau grup manual) diringkas
    // jadi SATU item pada posisi anggota pertamanya di
    // halaman ini bila grupnya berisi lebih dari satu baris;
    // rincian anggotanya dibuka lewat modal isi grup. Grup
    // satu baris dan baris non-belanja tampil seperti biasa.
    // Item grup membawa data ringkas seluruh grup (semua
    // anggota dari basis data, bukan hanya yang tampil di
    // halaman ini).
    $barisTampil = [];
    $grupDiHalaman = []; // kunci => daftar anggota grup (null bila grup satu baris)
    $kunciTampil = [];
    foreach ($baris as $b) {
      $kunciB = $kunciPerBaris[$b->id] ?? null;
      if ($kunciB === null) {
        $barisTampil[] = ['tipe' => 'baris', 'b' => $b, 'gm' => null, 'grup' => null];
        continue;
      }
      if (isset($kunciTampil[$kunciB])) {
        continue;
      }
      $kunciTampil[$kunciB] = true;
      if (!array_key_exists($kunciB, $grupDiHalaman)) {
        $anggotaGrup = $this->barisGrupKwitansi($sekolahId, $b, $kunciB);
        // Grup manual selalu diringkas (aliasnya harus
        // tampil walau anggotanya tinggal satu baris, mis.
        // bagian pecahan berisi satu baris); grup bawaan
        // ARKAS hanya diringkas bila anggotanya banyak.
        $grupDiHalaman[$kunciB] = (count($anggotaGrup) > 1 || str_starts_with($kunciB, 'manual:'))
          ? $anggotaGrup
          : null;
      }
      $anggota = $grupDiHalaman[$kunciB];
      if ($anggota === null) {
        $barisTampil[] = ['tipe' => 'baris', 'b' => $b, 'gm' => null, 'grup' => null];
        continue;
      }
      // Data ringkas grup multi-baris; khusus grup manual
      // ditambah identitas aliasnya (nama & jenis).
      $gm = null;
      if (str_starts_with($kunciB, 'manual:')) {
        $gid = (int) substr($kunciB, 7);
        $record = BosSpjGrupManual::where('sekolah_id', $sekolahId)->find($gid);
        $kunciAsal = collect($anggota)->map(fn($r) => $this->kunciGrupKwitansi($r))->unique();
        $gm = [
          'id' => $gid,
          'nama' => $record?->nama,
          'jenis' => $kunciAsal->count() > 1 ? 'gabungan' : 'pecahan',
        ];
      }
      $barisTampil[] = [
        'tipe' => 'grup',
        'b' => $b,
        'gm' => $gm,
        'grup' => [
          'kunci' => $kunciB,
          'anggota' => $anggota,
          'total' => array_sum(array_map(fn($r) => (float) $r->saldo, $anggota)),
        ],
      ];
    }

    // Nominal pajak per grup untuk kolom Pajak: baris
    // Pajak Belanja Terima (jenis pajak) yang notanya
    // termasuk grup ini, dijumlah per label pajaknya
    // (PPN / PPh 21 / ... dari labelPajak() baris pajak).
    // Peta nota -> kunci grup dibangun dari baris halaman
    // (lewat kunciPerBaris) dan dilengkapi dari anggota
    // grup pada barisTampil (nota yang anggotanya tidak
    // tampil semua di halaman ini).
    $petaNotaKunci = [];
    foreach ($baris as $b) {
      $kunciB = $kunciPerBaris[$b->id] ?? null;
      if ($kunciB && !empty($b->id_kas_nota)) {
        $petaNotaKunci[$b->id_kas_nota] = $kunciB;
      }
    }
    foreach ($barisTampil as $item) {
      if (!($item['grup'] ?? null)) {
        continue;
      }
      foreach ($item['grup']['anggota'] as $r) {
        if (!empty($r->id_kas_nota) && !isset($petaNotaKunci[$r->id_kas_nota])) {
          $petaNotaKunci[$r->id_kas_nota] = $item['grup']['kunci'];
        }
      }
    }
    $pajakPerKunci = [];
    if ($petaNotaKunci) {
      $barisPajak = BosKasUmum::where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_ref_bku', self::JENIS_PAJAK)
        ->whereIn('id_kas_nota', array_keys($petaNotaKunci))
        ->get();
      foreach ($barisPajak as $rp) {
        $kunciP = $petaNotaKunci[$rp->id_kas_nota] ?? null;
        if (!$kunciP) {
          continue;
        }
        $label = $rp->labelPajak() ?: 'Pajak';
        $pajakPerKunci[$kunciP][$label] = ($pajakPerKunci[$kunciP][$label] ?? 0.0) + (float) $rp->saldo;
      }
    }
    $pajakGrup = [];
    foreach ($baris as $b) {
      $kunciB = $kunciPerBaris[$b->id] ?? null;
      if ($kunciB && !empty($pajakPerKunci[$kunciB])) {
        $pajakGrup[$b->id] = collect($pajakPerKunci[$kunciB])
          ->map(fn($jml, $label) => ['label' => $label, 'jumlah' => $jml])
          ->values()
          ->all();
      }
    }

    // Kode & nama kegiatan per baris untuk kolom
    // Rekening: baris BKU -> periode RAPBS (id_rapbs_
    // periode) -> baris RAPBS (id_rapbs_arkas) -> ref
    // kode (id_ref_kode), pola realisasi Kertas Kerja.
    // Join ref kode SENGAJA tanpa syarat tahun: varian
    // ref_kode per id_ref_kode berbagi id_kode &
    // uraian_kode yang sama, tetapi tahun variannya
    // tidak mencakup semua tahun anggaran baris RAPBS
    // (terverifikasi dari data asli 2026-11-28: dengan
    // syarat tahun, sebagian baris anggaran — honor
    // 0013, ATK 0024 — kegiatannya tidak ketemu).
    // Baris tanpa hubungan periode (mis. saldo awal)
    // tidak punya kegiatan.
    $kegiatanPerBaris = [];
    // Id baris halaman diambil lewat getCollection():
    // collect() atas paginator menghasilkan metadata
    // halamannya (current_page, total, ...), BUKAN
    // baris-barisnya — pola yang sama seperti pemakaian
    // petaGrupManual di method ini.
    $idsKegiatan = $baris->getCollection()->pluck('id')->all();
    foreach ($barisTampil as $itemTampil) {
      if (($itemTampil['tipe'] ?? '') === 'grup') {
        foreach ($itemTampil['grup']['anggota'] ?? [] as $anggotaTampil) {
          $idsKegiatan[] = $anggotaTampil->id;
        }
      }
    }
    $idsKegiatan = array_values(array_unique($idsKegiatan));
    if ($idsKegiatan) {
      $refKodeKegiatan = BosRefKode::query()
        ->select('id_ref_kode')
        ->selectRaw('MIN(id_kode) as id_kode, MIN(uraian_kode) as uraian_kode')
        ->groupBy('id_ref_kode');
      $barisKegiatan = BosKasUmum::query()
        ->from('bos_kas_umum')
        ->join('bos_rapbs_periode', function ($j) {
          $j->on('bos_rapbs_periode.id_arkas', '=', 'bos_kas_umum.id_rapbs_periode')
            ->on('bos_rapbs_periode.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
        })
        ->join('bos_rapbs', function ($j) {
          $j->on('bos_rapbs.id_arkas', '=', 'bos_rapbs_periode.id_rapbs_arkas')
            ->on('bos_rapbs.sekolah_id', '=', 'bos_rapbs_periode.sekolah_id');
        })
        ->leftJoinSub($refKodeKegiatan, 'ref_kode_kegiatan', function ($j) {
          $j->on('ref_kode_kegiatan.id_ref_kode', '=', 'bos_rapbs.id_ref_kode');
        })
        ->where('bos_kas_umum.sekolah_id', $sekolahId)
        ->whereIn('bos_kas_umum.id', $idsKegiatan)
        ->where('bos_rapbs.soft_delete', false)
        ->get([
          'bos_kas_umum.id as kas_id',
          'ref_kode_kegiatan.id_kode as kode_kegiatan',
          'ref_kode_kegiatan.uraian_kode as nama_kegiatan',
        ]);
      foreach ($barisKegiatan as $bk) {
        if ($bk->kode_kegiatan !== null && $bk->kode_kegiatan !== '') {
          $kegiatanPerBaris[$bk->kas_id] = [
            'kode' => $bk->kode_kegiatan,
            'nama' => $bk->nama_kegiatan,
          ];
        }
      }
    }

    return view('content.kelola-bos.bku', $konteks + [
      'baris' => $baris,
      'totalNominal' => $totalNominal,
      'totalAnggaran' => $totalAnggaran,
      'tahunAnggaran' => $tahunAnggaran,
      'sumberTerpilih' => $sumberTerpilih,
      'namaSumberTerpilih' => $namaSumberTerpilih,
      'daftarSumberDana' => $this->daftarSumberDana($sekolahId),
      'kartuDana' => $kartuDana,
      'kartuAnggaran' => $kartuAnggaran,
      'daftarTahun' => $daftarTahun,
      'tahunTerpilih' => $tahunEfektif,
      'daftarJenis' => $daftarJenis,
      'daftarRekening' => $daftarRekening,
      'urut' => $urutKunci,
      'arah' => $arah,
      'petaAlias' => [],
      'bisaTulisAlias' => $this->bisaTulisAlias(),
      'bisaTulisPenerima' => $this->bisaTulisAlias(),
      'jumlahPenerima' => $jumlahPenerima,
      'namaPenerima' => $namaPenerima,
      'metaGrup' => $metaGrup,
      'kunciPerBaris' => $kunciPerBaris,
      'barisTampil' => $barisTampil,
      'pajakGrup' => $pajakGrup,
      'kegiatanPerBaris' => $kegiatanPerBaris,
    ]);
  }

  // ── SPJ Kwitansi (Fase 1) ─────────────────────────────
  //
  // Kwitansi SPJ dibuat dari baris belanja BKU, digabung
  // per nota / nomor bukti (disetujui user 2026-11-26):
  // satu nota ARKAS adalah satu pembayaran ke satu toko,
  // jadi satu kwitansi per nota — baris-baris barangnya
  // menjadi rincian di badan kwitansi. Nomor urut
  // kwitansi adalah posisi grup dalam tahun transaksinya
  // (deterministik, tanpa tabel penyimpanan).

  /** Memo penandatangan per sekolah selama satu request. */
  private array $memoPenandatangan = [];

  /** Peta baris → kunci grup manual ('manual:{id}') untuk sekumpulan id baris. */
  private function petaGrupManual(int $sekolahId, array $barisIds): array
  {
    if (!$barisIds) {
      return [];
    }

    return BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
      ->whereIn('bos_kas_umum_id', $barisIds)
      ->pluck('grup_manual_id', 'bos_kas_umum_id')
      ->map(fn($g) => 'manual:' . $g)
      ->all();
  }

  /** Kunci grup efektif satu baris: grup manual menang atas pengelompokan ARKAS. */
  private function kunciGrupEfektif(int $sekolahId, BosKasUmum $b): string
  {
    $peta = $this->petaGrupManual($sekolahId, [$b->id]);

    return $peta[$b->id] ?? $this->kunciGrupKwitansi($b);
  }

  /**
   * Baris-baris satu grup kwitansi menurut kunci efektif,
   * urut tanggal lalu id. Untuk kunci bawaan ARKAS
   * (nota/bukti), baris yang sedang menjadi anggota grup
   * manual TIDAK ikut — pengelompokan efektif mereka
   * adalah grup manualnya, konsisten dengan grupKwitansi()
   * yang mengelompokkan koleksi menurut kunci efektif
   * per baris.
   */
  private function barisGrupKwitansi(int $sekolahId, BosKasUmum $baris, string $kunci): array
  {
    $q = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA);
    if (str_starts_with($kunci, 'manual:')) {
      $grupId = (int) substr($kunci, 7);
      $ids = BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
        ->where('grup_manual_id', $grupId)
        ->pluck('bos_kas_umum_id');
      $q->whereIn('id', $ids);
    } elseif (str_starts_with($kunci, 'nota:')) {
      $q->where('id_kas_nota', $baris->id_kas_nota)
        ->whereNotIn('id', BosSpjGrupAnggota::where('sekolah_id', $sekolahId)->select('bos_kas_umum_id'));
    } elseif (str_starts_with($kunci, 'bukti:')) {
      $q->whereNull('id_kas_nota')
        ->where('no_bukti', $baris->no_bukti)
        ->whereDate('tanggal_transaksi', $baris->tanggal_transaksi)
        ->whereNotIn('id', BosSpjGrupAnggota::where('sekolah_id', $sekolahId)->select('bos_kas_umum_id'));
    } else {
      $q->where('id', $baris->id);
    }

    return $q->orderBy('tanggal_transaksi')->orderBy('id')->get()->all();
  }

  /**
   * Kunci pengelompokan kwitansi untuk satu baris BKU.
   * Bila peta grup manual diberikan dan baris ini anggota
   * grup manual, kunci manual yang berlaku; selain itu
   * pengelompokan bawaan ARKAS (nota → bukti → baris).
   */
  private function kunciGrupKwitansi(BosKasUmum $b, ?array $petaManual = null): string
  {
    if ($petaManual !== null && isset($petaManual[$b->id])) {
      return $petaManual[$b->id];
    }

    if (!empty($b->id_kas_nota)) {
      return 'nota:' . $b->id_kas_nota;
    }

    $noBukti = trim((string) ($b->no_bukti ?? ''));
    if ($noBukti !== '') {
      return 'bukti:' . $noBukti . '|' . ($b->tanggal_transaksi?->format('Y-m-d') ?? '');
    }

    return 'baris:' . $b->id;
  }

  /**
   * Kelompokkan baris-baris menjadi grup kwitansi. Hanya
   * baris belanja (JENIS_BELANJA) yang belum terhapus.
   *
   * @return array<string, BosKasUmum[]>
   */
  private function grupKwitansi($baris, ?array $petaManual = null): array
  {
    $grup = [];
    foreach ($baris as $b) {
      if ($b->soft_delete) {
        continue;
      }
      if (!in_array((int) $b->id_ref_bku, self::JENIS_BELANJA, true)) {
        continue;
      }
      $grup[$this->kunciGrupKwitansi($b, $petaManual)][] = $b;
    }

    return $grup;
  }

  /** Urutkan grup menurut tanggal transaksi paling awal grup, lalu id baris terkecil grup. */
  private function urutkanGrupKwitansi(array $grup): array
  {
    $meta = fn(array $rows) => [
      collect($rows)->map(fn($r) => $r->tanggal_transaksi?->getTimestamp() ?? 0)->min(),
      collect($rows)->min('id'),
    ];
    uasort($grup, fn($a, $b) => $meta($a) <=> $meta($b));

    return $grup;
  }

  /** Posisi grup (1-based) di antara semua grup belanja sekolah pada tahun itu. */
  private function nomorUrutKwitansi(int $sekolahId, string $kunciGrup, int $tahun): int
  {
    $baris = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->whereYear('tanggal_transaksi', $tahun)
      ->orderBy('tanggal_transaksi')
      ->orderBy('id')
      ->get();

    $peta = $this->petaGrupManual($sekolahId, $baris->pluck('id')->all());
    $grup = $this->urutkanGrupKwitansi($this->grupKwitansi($baris, $peta));
    $posisi = array_search($kunciGrup, array_keys($grup), true);

    return $posisi === false ? 0 : $posisi + 1;
  }

  /**
   * Penandatangan kwitansi: Kepala Sekolah dari detail
   * sekolah (cadangan PTK ber-jabatan Kepala Sekolah) dan
   * Bendahara dari PTK (jabatan atau peran tambahan).
   *
   * @return array{kepala: array{nama: ?string, nip: ?string}, bendahara: array{nama: ?string, nip: ?string}}
   */
  private function penandatangan(int $sekolahId, $sekolah): array
  {
    if (isset($this->memoPenandatangan[$sekolahId])) {
      return $this->memoPenandatangan[$sekolahId];
    }

    $kepala = ['nama' => null, 'nip' => null];
    $detail = $sekolah?->detailTerbaru;
    if ($detail && trim((string) $detail->kepala_sekolah) !== '') {
      $kepala = ['nama' => $detail->kepala_sekolah, 'nip' => $detail->nip];
    } else {
      $ptkKepala = Ptk::where('sekolah_id', $sekolahId)
        ->where('jabatan', 'Kepala Sekolah')
        ->where('status', 'aktif')
        ->first();
      if ($ptkKepala) {
        $kepala = ['nama' => $ptkKepala->nama_lengkap, 'nip' => $ptkKepala->nip];
      }
    }

    $bendahara = ['nama' => null, 'nip' => null];
    $ptkBendahara = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->where(function ($q) {
        $q->where('jabatan', 'Bendahara')
          ->orWhereJsonContains('peran_tambahan', 'Bendahara');
      })
      ->first();
    if ($ptkBendahara) {
      $bendahara = ['nama' => $ptkBendahara->nama_lengkap, 'nip' => $ptkBendahara->nip];
    }

    return $this->memoPenandatangan[$sekolahId] = [
      'kepala' => $kepala,
      'bendahara' => $bendahara,
    ];
  }

  /** Susun data satu kwitansi dari grup barisnya. */
  private function dataKwitansi(int $sekolahId, array $grup, int $nomorUrut): array
  {
    $grup = array_values($grup);
    $pertama = $grup[0];

    // Grup manual: nama aliasnya menjadi uraian
    // pembayaran kwitansi (menggantikan uraian baris /
    // teks generik) — keanggotaan cukup dicek dari baris
    // pertama karena seluruh grup berbagi kunci yang sama.
    $grupManual = null;
    $anggotaManual = BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
      ->where('bos_kas_umum_id', $pertama->id)
      ->first();
    if ($anggotaManual) {
      $grupManual = BosSpjGrupManual::where('sekolah_id', $sekolahId)
        ->find($anggotaManual->grup_manual_id);
    }

    // Nama tampilan per grup (meta SPJ): menggantikan
    // uraian kwitansi khusus grup ini bila diisi —
    // di bawah nama grup manual, di atas uraian asli.
    $namaTampilGrup = null;
    if (!$grupManual) {
      $metaNama = BosSpjGrup::where('sekolah_id', $sekolahId)
        ->where('grup_kunci', $this->kunciGrupKwitansi($pertama))
        ->first();
      $namaTampilGrup = $metaNama?->nama ?: null;
    }

    $nota = null;
    if (!empty($pertama->id_kas_nota)) {
      $nota = BosKasNota::where('id_arkas', $pertama->id_kas_nota)
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->first();
    }

    $total = array_sum(array_map(fn($b) => (float) $b->saldo, $grup));

    $rekeningUnik = collect($grup)->pluck('kode_rekening')->filter()->unique()->values();
    $kodeRekening = $rekeningUnik->count() === 1
      ? $rekeningUnik->first()
      : ($nota->kode_rekening ?? null);

    $fmtVolume = function ($v) {
      if ($v === null) {
        return null;
      }
      $s = number_format((float) $v, 2, ',', '.');
      return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
    };

    $tanggal = $nota->tanggal_nota ?? $pertama->tanggal_transaksi;
    $sekolah = Sekolah::with('detailTerbaru')->find($sekolahId);

    return [
      'nomor' => str_pad((string) $nomorUrut, 3, '0', STR_PAD_LEFT),
      'no_bukti' => $nota->no_bukti ?? ($pertama->no_bukti ?? null),
      'kode_rekening' => $kodeRekening,
      'uraian' => $grupManual
        ? $grupManual->nama
        : ($namaTampilGrup ?? (count($grup) === 1
          ? $pertama->uraian
          : 'Pembayaran belanja barang/jasa dengan rincian sebagai berikut')),
      'grup_manual' => (bool) $grupManual,
      'nama_alias' => $grupManual?->nama,
      'nama_grup' => $namaTampilGrup,
      'rincian' => array_map(fn($b) => [
        'uraian' => $b->uraian,
        'kode_rekening' => $b->kode_rekening,
        'volume' => $fmtVolume($b->volume),
        'saldo' => (float) $b->saldo,
      ], $grup),
      'total' => $total,
      'terbilang' => Terbilang::rupiah($total),
      'tanggal' => $tanggal?->format('d-m-Y'),
      'penerima' => $nota->nama_toko ?? null,
      'ttd' => $this->penandatangan($sekolahId, $sekolah),
    ];
  }

  /** Daftar kwitansi untuk satu grup: per penerima SPJ bila grup
   *  sudah punya penerima, selain itu satu kwitansi per nota. */
  private function daftarKwitansiGrup(int $sekolahId, string $kunci, array $grup, int $nomorUrut): array
  {
    $grup = array_values($grup);
    $dasar = $this->dataKwitansi($sekolahId, $grup, $nomorUrut);

    $penerima = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->orderBy('urutan')
      ->orderBy('id')
      ->get();
    if ($penerima->isEmpty()) {
      return [$dasar];
    }

    $fmtVolume = function ($v) {
      if ($v === null) {
        return null;
      }
      $s = number_format((float) $v, 2, ',', '.');
      return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
    };

    $uraian = (!empty($dasar['grup_manual']) || !empty($dasar['nama_grup']))
      ? $dasar['uraian']
      : (count($grup) === 1 ? $dasar['uraian'] : $grup[0]->uraian);

    $daftar = [];
    foreach ($penerima as $i => $p) {
      $rincian = [
        [
          'uraian' => $uraian,
          'kode_rekening' => $dasar['kode_rekening'],
          'volume' => $fmtVolume((float) $p->volume),
          'saldo' => (float) $p->jumlah,
        ],
      ];
      // Baris potongan per komponen rupiah — hanya
      // komponen yang nilainya > 0 yang tampil.
      $potongan = [
        ['Potongan PPN', (float) $p->ppn],
        ['Potongan PPh', (float) $p->pph],
        ['Potongan SSPD', (float) $p->sspd],
        ['Biaya Admin', (float) $p->biaya_admin],
      ];
      foreach ($potongan as [$labelPotongan, $nilaiPotongan]) {
        if ($nilaiPotongan > 0) {
          $rincian[] = [
            'uraian' => $labelPotongan,
            'kode_rekening' => null,
            'volume' => null,
            'saldo' => -$nilaiPotongan,
          ];
        }
      }

      $daftar[] = array_merge($dasar, [
        'nomor' => $dasar['nomor'] . '-' . ($i + 1),
        'uraian' => $uraian,
        'rincian' => $rincian,
        'total' => (float) $p->diterima,
        'terbilang' => Terbilang::rupiah((float) $p->diterima),
        'penerima' => $p->nama,
        'tampil_rincian' => true,
      ]);
    }

    return $daftar;
  }

  /**
   * Susun data Tanda Terima satu grup non-barang: daftar
   * penerima jenis 'orang' saja (baris toko tidak ikut —
   * Tanda Terima untuk pembayaran ke orang). Judul
   * kegiatan = alias grup manual bila ada, selain itu
   * uraian BKU grup. Kolom identitas memakai NIK (data
   * penerima/PTK menyimpan NIK, bukan NPWP).
   */
  private function dataTandaTerima(int $sekolahId, string $kunci, array $grup): array
  {
    $grup = array_values($grup);
    $dasar = $this->dataKwitansi($sekolahId, $grup, 0);

    $judul = !empty($dasar['grup_manual'])
      ? $dasar['uraian']
      : (count($grup) === 1 ? $dasar['uraian'] : $grup[0]->uraian);

    $penerima = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->where('jenis', 'orang')
      ->orderBy('urutan')
      ->orderBy('id')
      ->get();

    $fmtVolume = function ($v) {
      if ($v === null) {
        return null;
      }
      $s = number_format((float) $v, 2, ',', '.');
      return str_contains($s, ',') ? rtrim(rtrim($s, '0'), ',') : $s;
    };

    $baris = $penerima->map(fn($p) => [
      'nama' => $p->nama,
      'pangkat_gol' => $p->pangkat_gol,
      'nik' => $p->nik,
      'volume' => $fmtVolume((float) $p->volume),
      'satuan' => (float) $p->satuan,
      'jumlah' => $p->jumlah,
      'pph' => $p->pph,
      'diterima' => $p->diterima,
    ])->all();

    return [
      'kegiatan' => $judul,
      'no_bukti' => $dasar['no_bukti'],
      'kode_rekening' => $dasar['kode_rekening'],
      'tanggal' => collect($grup)->max('tanggal_transaksi'),
      'baris' => $baris,
      'total_jumlah' => array_sum(array_column($baris, 'jumlah')),
      'total_pph' => array_sum(array_column($baris, 'pph')),
      'total_diterima' => array_sum(array_column($baris, 'diterima')),
      'ttd' => $dasar['ttd'],
    ];
  }

  /**
   * Deteksi kategori non-barang dari kode rekening
   * (struktur kode rekening belanja daerah yang dipakai
   * ARKAS). Non-barang = pembayaran ke ORANG (sasaran
   * dokumen Tanda Terima), bukan pembelian barang dari
   * toko. Aturannya, menurut segmen kode yang dipisah
   * titik:
   * - 5.1.01...    Belanja Pegawai (honorarium &
   *                tambahan penghasilan) → non-barang.
   *                Contoh: 5.1.01.02.01.0001.
   * - 5.1.02.04... Belanja Perjalanan Dinas (transport
   *                orang) → non-barang.
   *                Contoh: 5.1.02.04.01.0003.
   * - 5.1.02.02.01... Belanja Jasa Kantor rincian
   *                honorarium (narasumber / moderator /
   *                panitia) & belanja jasa tenaga
   *                (administrasi, pendidikan,
   *                kebersihan, keamanan, ahli, dst)
   *                → non-barang. Contoh:
   *                5.1.02.02.01.0003, 5.1.02.02.01.0026.
   * Di luar itu barang: mis. 5.1.02.01.01.0024 (belanja
   * barang pakai habis / ATK) dan 5.2... (belanja
   * modal). Kode kosong / tak dikenal → barang.
   * Hasil deteksi bisa diganti manual per grup lewat
   * kolom kategori di bos_spj_grup (null = otomatis).
   */
  private function deteksiNonBarang(?string $kodeRekening): bool
  {
    if (!$kodeRekening) {
      return false;
    }
    $s = explode('.', trim($kodeRekening));
    if (($s[0] ?? '') !== '5' || ($s[1] ?? '') !== '1') {
      return false;
    }
    // Belanja Pegawai (5.1.01...): honorarium & tambahan penghasilan.
    if (($s[2] ?? '') === '01') {
      return true;
    }
    // Belanja Perjalanan Dinas (5.1.02.04...): transport orang.
    if (($s[2] ?? '') === '02' && ($s[3] ?? '') === '04') {
      return true;
    }
    // Belanja Jasa Kantor (5.1.02.02.01...): honorarium narasumber/
    // moderator/panitia & belanja jasa tenaga (administrasi,
    // pendidikan, kebersihan, keamanan, ahli, dst) — pembayaran
    // ke orang, bukan pembelian barang dari toko.
    if (($s[2] ?? '') === '02' && ($s[3] ?? '') === '02' && ($s[4] ?? '') === '01') {
      return true;
    }
    return false;
  }

  /** Grup dianggap non-barang bila SALAH SATU barisnya terdeteksi non-barang. */
  private function deteksiNonBarangGrup(array $grup): bool
  {
    foreach ($grup as $b) {
      if ($this->deteksiNonBarang($b->kode_rekening ?? null)) {
        return true;
      }
    }
    return false;
  }

  /**
   * Kategori grup SPJ: 'barang' | 'barang_konsumsi' |
   * 'non_barang'. Deteksi otomatis hanya membedakan
   * non-barang (dari kode rekening) vs barang;
   * 'barang_konsumsi' hanya lewat pilihan manual.
   * Override manual tersimpan di bos_spj_grup.kategori
   * (null = ikut deteksi).
   *
   * @return array{deteksi: string, manual: ?string, efektif: string}
   */
  private function kategoriGrup(array $grup, ?BosSpjGrup $meta): array
  {
    $deteksi = $this->deteksiNonBarangGrup($grup) ? 'non_barang' : 'barang';
    $manual = $meta?->kategori;
    return [
      'deteksi' => $deteksi,
      'manual' => $manual,
      'efektif' => $manual ?? $deteksi,
    ];
  }

  /** Cetak kwitansi untuk grup nota/bukti dari satu baris BKU. */
  public function cetakKwitansi(Request $request, int $id)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];
    if (!$sekolahId) {
      abort(404);
    }

    $baris = BosKasUmum::where('id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->first();
    if (!$baris) {
      abort(404);
    }

    $kunci = $this->kunciGrupEfektif($sekolahId, $baris);
    $grup = $this->barisGrupKwitansi($sekolahId, $baris, $kunci);

    $tahun = (int) ($baris->tanggal_transaksi?->format('Y') ?? now()->format('Y'));
    $nomor = $this->nomorUrutKwitansi($sekolahId, $kunci, $tahun);
    $daftar = $this->daftarKwitansiGrup($sekolahId, $kunci, $grup, $nomor);

    return Pdf::loadView('content.kelola-bos.kwitansi-pdf', [
      'daftarKwitansi' => $daftar,
      'sekolah' => $konteks['sekolah'] ?? Sekolah::with('detailTerbaru')->find($sekolahId),
    ])->setPaper('a4', 'portrait')
      ->stream('kwitansi-' . ($daftar[0]['no_bukti'] ?? $id) . '.pdf');
  }

  /**
   * Cetak Tanda Terima untuk satu grup BKU — hanya grup
   * berkategori efektif non-barang yang punya minimal
   * satu penerima jenis 'orang'; selain itu 404.
   */
  public function cetakTandaTerima(Request $request, int $id)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];
    if (!$sekolahId) {
      abort(404);
    }

    $baris = BosKasUmum::where('id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->first();
    if (!$baris) {
      abort(404);
    }

    $kunci = $this->kunciGrupEfektif($sekolahId, $baris);
    $grup = $this->barisGrupKwitansi($sekolahId, $baris, $kunci);

    $meta = BosSpjGrup::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    if ($this->kategoriGrup($grup, $meta)['efektif'] !== 'non_barang') {
      abort(404);
    }

    $data = $this->dataTandaTerima($sekolahId, $kunci, $grup);
    if (empty($data['baris'])) {
      abort(404);
    }

    return Pdf::loadView('content.kelola-bos.tanda-terima-pdf', [
      'tandaTerima' => $data,
      'sekolah' => $konteks['sekolah'] ?? Sekolah::with('detailTerbaru')->find($sekolahId),
    ])->setPaper('a4', 'landscape')
      ->stream('tanda-terima-' . ($data['no_bukti'] ?? $id) . '.pdf');
  }

  /** Cetak kwitansi semua transaksi belanja sesuai saringan halaman BKU. */
  public function cetakKwitansiMassal(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];
    if (!$sekolahId) {
      abort(404);
    }

    // Saringan sama seperti bku(), selalu dibatasi jenis belanja.
    $dasar = fn() => BosKasUmum::query()
      ->from('bos_kas_umum')
      ->leftJoin('bos_ref_bku', function ($j) {
        $j->on('bos_ref_bku.id_ref_bku', '=', 'bos_kas_umum.id_ref_bku');
      })
      ->leftJoin('bos_kas_nota', function ($j) {
        $j->on('bos_kas_nota.id_arkas', '=', 'bos_kas_umum.id_kas_nota')
          ->on('bos_kas_nota.sekolah_id', '=', 'bos_kas_umum.sekolah_id');
      })
      ->where('bos_kas_umum.sekolah_id', $sekolahId)
      ->where('bos_kas_umum.soft_delete', false);

    $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
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
    $tahunEfektif = $this->tahunEfektif($request, $daftarTahun);

    $saring = function ($q) use ($request, $tahunEfektif) {
      $q->when($tahunEfektif !== null, fn($x) => $x->whereYear('bos_kas_umum.tanggal_transaksi', $tahunEfektif))
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

    $baris = $saring($dasar())
      ->whereIn('bos_kas_umum.id_ref_bku', self::JENIS_BELANJA)
      ->select('bos_kas_umum.*')
      ->orderBy('bos_kas_umum.tanggal_transaksi')
      ->orderBy('bos_kas_umum.id')
      ->get();

    $peta = $this->petaGrupManual($sekolahId, $baris->pluck('id')->all());
    $grup = $this->urutkanGrupKwitansi($this->grupKwitansi($baris, $peta));

    if (!$grup) {
      return redirect()->route('kelola-bos.bku', $request->query())
        ->with('error', 'Tidak ada transaksi belanja pada saringan ini.');
    }

    $daftarKwitansi = [];
    foreach ($grup as $kunci => $rows) {
      $tahunGrup = (int) (collect($rows)->map(fn($r) => $r->tanggal_transaksi?->format('Y'))->filter()->min()
        ?? now()->format('Y'));
      $nomor = $this->nomorUrutKwitansi($sekolahId, (string) $kunci, $tahunGrup);
      $daftarKwitansi = array_merge($daftarKwitansi, $this->daftarKwitansiGrup($sekolahId, (string) $kunci, $rows, $nomor));
    }

    return Pdf::loadView('content.kelola-bos.kwitansi-pdf', [
      'daftarKwitansi' => $daftarKwitansi,
      'sekolah' => $konteks['sekolah'] ?? Sekolah::with('detailTerbaru')->find($sekolahId),
    ])->setPaper('a4', 'portrait')
      ->stream('kwitansi-massal.pdf');
  }

  // ── SPJ Penerima (Fase 2) ─────────────────────────────
  //
  // Daftar penerima per grup transaksi BKU (grup yang
  // sama dengan kwitansi: per nota / nomor bukti).
  // Penerima diambil dari PTK, dari induk penerima
  // non-PTK (tabel_penerima), atau diketik manual —
  // semuanya disimpan sebagai SNAPSHOT di
  // bos_spj_penerima agar dokumen SPJ tidak berubah
  // saat data induk berubah. Semua method di sini JSON
  // untuk modal editor di halaman BKU; konteks sekolah
  // SELALU diambil dari baris BKU pada URL (pola yang
  // sama dengan cetakKwitansi), tidak dari parameter
  // bebas, agar operator sekolah lain tidak bisa
  // mengintip / mengubah data sekolah ini.

  /**
   * Status tampil satu grup SPJ. Bila grup punya penerima,
   * status DITURUNKAN dari penanda selesai tiap penerima:
   * semua selesai -> 'selesai', sebagian -> 'sebagian',
   * belum ada -> 'belum'. Bila grup tanpa penerima, status
   * mengikuti meta grup (bos_spj_grup.status).
   *
   * @return array{status_tampil: string, jumlah_penerima: int, jumlah_selesai: int}
   */
  private function statusTampilGrup(int $sekolahId, string $kunci): array
  {
    $jumlah = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->count();
    if ($jumlah > 0) {
      $selesai = BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->where('grup_kunci', $kunci)
        ->where('selesai', true)
        ->count();
      return [
        'status_tampil' => $selesai >= $jumlah ? 'selesai' : ($selesai > 0 ? 'sebagian' : 'belum'),
        'jumlah_penerima' => $jumlah,
        'jumlah_selesai' => $selesai,
      ];
    }
    $meta = BosSpjGrup::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    return [
      'status_tampil' => ($meta?->status ?? 'belum') === 'selesai' ? 'selesai' : 'belum',
      'jumlah_penerima' => 0,
      'jumlah_selesai' => 0,
    ];
  }

  /**
   * Konteks grup penerima dari satu baris BKU: sekolah
   * aktif, baris, kunci grup, dan baris-baris grupnya.
   * Null bila baris tidak ada di sekolah aktif.
   */
  private function konteksGrupPenerima(Request $request, int $id, bool $wajibBelanja = true): ?array
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return null;
    }

    $q = BosKasUmum::where('id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('soft_delete', false);
    if ($wajibBelanja) {
      $q->whereIn('id_ref_bku', self::JENIS_BELANJA);
    }
    $baris = $q->first();
    if (!$baris) {
      return null;
    }

    $kunci = $this->kunciGrupEfektif($sekolahId, $baris);
    $grup = $this->barisGrupKwitansi($sekolahId, $baris, $kunci);

    return ['sekolahId' => $sekolahId, 'baris' => $baris, 'kunci' => $kunci, 'grup' => $grup];
  }

  /** Sekolah aktif dari konteks baris BKU (untuk method master & ubah/hapus snapshot). */
  private function sekolahDariBaris(Request $request, int $id): ?int
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return null;
    }

    $ada = BosKasUmum::where('id', $id)
      ->where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->exists();

    return $ada ? $sekolahId : null;
  }

  /** Bentuk array snapshot penerima untuk respons JSON editor. */
  private function snapshotKeArray(BosSpjPenerima $p): array
  {
    return [
      'id' => $p->id,
      'ptk_id' => $p->ptk_id,
      'penerima_id' => $p->penerima_id,
      'jenis' => $p->jenis ?? 'orang',
      'nama' => $p->nama,
      'nip' => $p->nip,
      'pangkat_gol' => $p->pangkat_gol,
      'nik' => $p->nik,
      'no_rekening' => $p->no_rekening,
      'nama_bank' => $p->nama_bank,
      'volume' => (float) $p->volume,
      'satuan' => (float) $p->satuan,
      'jumlah' => $p->jumlah,
      'ppn' => $p->ppn,
      'pph' => $p->pph,
      'sspd' => $p->sspd,
      'biaya_admin' => $p->biaya_admin,
      'diterima' => $p->diterima,
      'urutan' => (int) $p->urutan,
      'selesai' => (bool) $p->selesai,
    ];
  }

  /** Urutan berikutnya untuk snapshot baru di satu grup. */
  private function urutanBerikutnya(int $sekolahId, string $kunci): int
  {
    return (int) BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->max('urutan') + 1;
  }

  /** Aturan validasi snapshot penerima (manual & ubah). */
  private function aturanSnapshot(): array
  {
    return [
      'nama' => 'required|string|max:255',
      'nip' => 'nullable|string|max:18',
      'pangkat_gol' => 'nullable|string|max:100',
      'nik' => 'nullable|digits:16',
      'no_rekening' => 'nullable|string|max:30',
      'nama_bank' => 'nullable|string|max:100',
      'volume' => 'required|numeric|min:0',
      'satuan' => 'required|numeric|min:0',
      'ppn_rp' => 'nullable|numeric|min:0',
      'pph_rp' => 'nullable|numeric|min:0',
      'sspd_rp' => 'nullable|numeric|min:0',
      'admin_rp' => 'nullable|numeric|min:0',
      'jenis' => 'nullable|in:orang,toko',
      'ptk_id' => 'nullable|integer',
      'penerima_id' => 'nullable|integer',
    ];
  }

  /**
   * Verifikasi tautan sumber snapshot terhadap sekolah
   * baris BKU: ptk_id harus PTK sekolah itu, penerima_id
   * harus induk penerima sekolah itu; selain itu null.
   * Jenis 'toko' tidak pernah tertaut.
   *
   * @return array{0: ?int, 1: ?int} [ptk_id, penerima_id]
   */
  private function tautanSumberPenerima(int $sekolahId, array $validated): array
  {
    if (($validated['jenis'] ?? 'orang') === 'toko') {
      return [null, null];
    }
    $ptkId = !empty($validated['ptk_id'])
      && Ptk::where('id', $validated['ptk_id'])->where('sekolah_id', $sekolahId)->exists()
      ? (int) $validated['ptk_id'] : null;
    $penerimaId = !empty($validated['penerima_id'])
      && Penerima::where('id', $validated['penerima_id'])->where('sekolah_id', $sekolahId)->exists()
      ? (int) $validated['penerima_id'] : null;
    return [$ptkId, $penerimaId];
  }

  /** Aturan validasi induk penerima non-PTK. */
  private function aturanMaster(): array
  {
    return [
      'nama' => 'required|string|max:255',
      'jenis' => 'nullable|in:orang,toko',
      'nik' => 'nullable|digits:16',
      'nip' => 'nullable|string|max:18',
      'pangkat_gol' => 'nullable|string|max:100',
      'pemilik' => 'nullable|string|max:255',
      'npwp' => 'nullable|string|max:30',
      'no_hp' => 'nullable|string|max:30',
      'alamat' => 'nullable|string|max:1000',
      'no_rekening' => 'nullable|string|max:30',
      'nama_bank' => 'nullable|string|max:100',
    ];
  }

  /** Data awal editor penerima untuk satu grup transaksi BKU. */
  public function penerimaData(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'baris' => $baris, 'kunci' => $kunci, 'grup' => $grup] = $konteks;

    $ringkas = $this->dataKwitansi($sekolahId, $grup, 0);

    $penerima = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->orderBy('urutan')
      ->orderBy('id')
      ->get()
      ->map(fn(BosSpjPenerima $p) => $this->snapshotKeArray($p))
      ->all();

    $sumberPtk = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->orderBy('nama_ptk')
      ->get()
      ->map(fn(Ptk $p) => [
        'id' => $p->id,
        'nama' => $p->nama_lengkap,
        'nip' => $p->nip,
        'pangkat_golongan' => $p->pangkat_golongan,
        'nik' => $p->nik,
        'no_rekening' => $p->no_rekening,
        'nama_bank' => $p->nama_bank,
      ])
      ->all();

    $sumberPenerima = Penerima::where('sekolah_id', $sekolahId)
      ->orderBy('nama')
      ->get()
      ->map(fn(Penerima $p) => [
        'id' => $p->id,
        'nama' => $p->nama,
        'jenis' => $p->jenis ?: 'orang',
        'nik' => $p->nik,
        'nip' => $p->nip,
        'pangkat_gol' => $p->pangkat_gol,
        'pemilik' => $p->pemilik,
        'npwp' => $p->npwp,
        'no_hp' => $p->no_hp,
        'alamat' => $p->alamat,
        'no_rekening' => $p->no_rekening,
        'nama_bank' => $p->nama_bank,
      ])
      ->all();

    // Meta grup: penanda SIPLah + nomor invoice dari nota
    // grup (nota baris pertama yang punya nota), dan
    // status / nomor VA dari tabel kerja bos_spj_grup.
    // Nomor VA tidak ada di data ARKAS (details nota
    // hanya memuat invoice) — ia isian manual di meta.
    $nota = null;
    $barisNota = collect($grup)->first(fn($b) => !empty($b->id_kas_nota));
    if ($barisNota) {
      $nota = BosKasNota::where('id_arkas', $barisNota->id_kas_nota)
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->first();
    }
    $invoiceSiplah = null;
    $detailNota = is_array($nota?->details)
      ? $nota->details
      : json_decode((string) ($nota?->details ?? ''), true);
    if (is_array($detailNota)) {
      $invoiceSiplah = $detailNota['siplahResponse']['invoice_number'] ?? null;
    }

    $meta = BosSpjGrup::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();

    // Kategori grup: deteksi otomatis dari kode rekening
    // baris-baris grup, dengan override manual dari
    // kolom kategori meta (null = ikut deteksi).
    $kategori = $this->kategoriGrup($grup, $meta);

    // Pajak nota menurut BKU (baris Pajak Belanja
    // Terima yang tertaut nota grup ini), dipetakan
    // ke bidang potongan: PPN -> ppn, semua PPh ->
    // pph, SSPD -> sspd. Dipakai sebagai isian
    // bawaan formulir penerima.
    $pajakBku = ['ppn' => 0.0, 'pph' => 0.0, 'sspd' => 0.0];
    $notaIds = collect($grup)->pluck('id_kas_nota')->filter()->unique()->values();
    if ($notaIds->isNotEmpty()) {
      $barisPajak = BosKasUmum::where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->where('id_ref_bku', self::JENIS_PAJAK)
        ->whereIn('id_kas_nota', $notaIds)
        ->get();
      foreach ($barisPajak as $rp) {
        if ($rp->is_ppn) {
          $pajakBku['ppn'] += (float) $rp->saldo;
        }
        if ($rp->is_pph_21 || $rp->is_pph_22 || $rp->is_pph_23 || $rp->is_pph_4) {
          $pajakBku['pph'] += (float) $rp->saldo;
        }
        if ($rp->is_sspd) {
          $pajakBku['sspd'] += (float) $rp->saldo;
        }
      }
    }

    // Potongan yang sudah dialokasikan ke penerima
    // tersimpan grup ini.
    $alokasiPajak = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->selectRaw('COALESCE(SUM(ppn_rp), 0) AS ppn, COALESCE(SUM(pph_rp), 0) AS pph, COALESCE(SUM(sspd_rp), 0) AS sspd')
      ->first();
    $pajakTeralokasi = [
      'ppn' => (float) ($alokasiPajak->ppn ?? 0),
      'pph' => (float) ($alokasiPajak->pph ?? 0),
      'sspd' => (float) ($alokasiPajak->sspd ?? 0),
    ];

    // Sumber nama toko untuk combobox editor: nama toko
    // unik dari semua nota sekolah ini beserta NPWP-nya.
    $sumberToko = BosKasNota::where('sekolah_id', $sekolahId)
      ->whereNotNull('nama_toko')->where('nama_toko', '<>', '')
      ->groupBy('nama_toko')
      ->selectRaw('nama_toko, MAX(npwp) AS npwp')
      ->orderBy('nama_toko')
      ->get()
      ->map(fn($t) => ['nama' => $t->nama_toko, 'npwp' => $t->npwp])
      ->all();

    // Status tampil grup: diturunkan dari penanda
    // selesai penerima bila grup punya penerima.
    $statusGrup = $this->statusTampilGrup($sekolahId, $kunci);

    return response()->json([
      'grup' => [
        'kunci' => $kunci,
        'baris_id' => $baris->id,
        'no_bukti' => $ringkas['no_bukti'],
        'uraian' => $ringkas['uraian'],
        'tanggal' => $ringkas['tanggal'],
        'total' => $ringkas['total'],
        'siplah' => (bool) ($nota?->is_beli_di_siplah ?? false),
        'invoice_siplah' => $invoiceSiplah,
        'nomor_va' => $meta?->nomor_va,
        'status' => $meta?->status ?? 'belum',
        'status_tampil' => $statusGrup['status_tampil'],
        'jumlah_penerima' => $statusGrup['jumlah_penerima'],
        'jumlah_selesai' => $statusGrup['jumlah_selesai'],
        'nama_toko' => $nota?->nama_toko,
        'kategori_deteksi' => $kategori['deteksi'],
        'kategori_manual' => $kategori['manual'],
        'kategori' => $kategori['efektif'],
        'pajak_bku' => $pajakBku,
        'pajak_teralokasi' => $pajakTeralokasi,
      ],
      'bisa_tulis' => $this->bisaTulisAlias(),
      'penerima' => $penerima,
      'sumber_ptk' => $sumberPtk,
      'sumber_penerima' => $sumberPenerima,
      'sumber_toko' => $sumberToko,
    ]);
  }

  /**
   * Simpan meta grup SPJ: saklar status (belum /
   * selesai), nomor VA pembayaran SIPLah (manual), dan
   * override kategori (opsional — hanya diubah bila
   * request memuat kuncinya; nilainya boleh null untuk
   * kembali ke deteksi otomatis).
   * Baris meta dibuat malas lewat updateOrCreate menurut
   * (sekolah, grup_kunci) dari baris BKU pada URL.
   */
  public function penerimaGrupUbah(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci, 'grup' => $grupBaris] = $konteks;

    $validated = $request->validate([
      'status' => 'required|in:' . implode(',', BosSpjGrup::STATUS),
      'nomor_va' => 'nullable|string|max:50',
      'kategori' => 'nullable|string|in:barang,barang_konsumsi,non_barang',
      'nama' => 'nullable|string|max:255',
    ]);

    $nomorVa = isset($validated['nomor_va']) && trim((string) $validated['nomor_va']) !== ''
      ? trim((string) $validated['nomor_va'])
      : null;

    // Atribut dibangun kondisional: kolom nomor_va dan
    // kategori hanya disentuh bila pemanggil mengirim
    // kuncinya — saklar status di baris tabel hanya
    // mengirim status sehingga tidak menimpa nomor VA
    // tersimpan, dan pemanggil lama tidak menimpa
    // override kategori yang sudah tersimpan. Kategori
    // memakai exists() (bukan has()) karena nilai null
    // eksplisit berarti "kembali ke deteksi otomatis"
    // dan has() mengabaikan nilai null.
    $atribut = ['status' => $validated['status']];
    if ($request->has('nomor_va')) {
      $atribut['nomor_va'] = $nomorVa;
    }
    if ($request->exists('kategori')) {
      $kat = $request->input('kategori');
      $atribut['kategori'] = ($kat === null || $kat === '') ? null : $kat;
    }
    // Nama tampilan grup: hanya disentuh bila kuncinya
    // dikirim; string kosong / null menghapus nama
    // (kembali ke uraian / alias biasa).
    if ($request->exists('nama')) {
      $namaGrup = trim((string) ($request->input('nama') ?? ''));
      $atribut['nama'] = $namaGrup === '' ? null : $namaGrup;
    }

    $meta = BosSpjGrup::updateOrCreate(
      ['sekolah_id' => $sekolahId, 'grup_kunci' => $kunci],
      $atribut
    );

    $kategori = $this->kategoriGrup($grupBaris, $meta);

    $statusGrup = $this->statusTampilGrup($sekolahId, $kunci);

    return response()->json([
      'success' => true,
      'message' => 'Keterangan grup SPJ diperbarui.',
      'status' => $meta->status,
      'nama' => $meta->nama,
      'nomor_va' => $meta->nomor_va,
      'kategori' => $kategori['efektif'],
      'kategori_manual' => $kategori['manual'],
      'kategori_deteksi' => $kategori['deteksi'],
      'status_tampil' => $statusGrup['status_tampil'],
      'jumlah_penerima' => $statusGrup['jumlah_penerima'],
      'jumlah_selesai' => $statusGrup['jumlah_selesai'],
    ]);
  }

  // ── SPJ Grup Manual (gabung / pecah nota) ────────────
  //
  // Pengelompokan manual menimpa pengelompokan bawaan
  // ARKAS lewat tabel bos_spj_grup_manual +
  // bos_spj_grup_anggota (kunci grup "manual:{id}").
  // Data cermin ARKAS tidak pernah diubah; membatalkan
  // grup manual mengembalikan baris-barisnya ke
  // pengelompokan nota / nomor bukti seperti semula.

  /** Isi satu grup transaksi BKU (modal isi grup & modal pecah di halaman BKU). */
  public function grupAnggota(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci, 'grup' => $grup, 'baris' => $barisKonteks] = $konteks;

    $manual = str_starts_with($kunci, 'manual:');
    $grupManualId = $manual ? (int) substr($kunci, 7) : null;
    $gm = $grupManualId
      ? BosSpjGrupManual::where('sekolah_id', $sekolahId)->find($grupManualId)
      : null;

    $jenis = null;
    if ($manual) {
      $kunciAsal = collect($grup)->map(fn($r) => $this->kunciGrupKwitansi($r))->unique();
      $jenis = $kunciAsal->count() > 1 ? 'gabungan' : 'pecahan';
    }

    // Nama rekening per baris: ref rekening dipilih
    // menurut kode + tahun transaksi baris (satu kode
    // punya nama per tahun di ref ARKAS) — pola yang sama
    // dengan subquery nama_rekening di bku().
    $kodeUnik = collect($grup)->pluck('kode_rekening')->filter()->unique()->values();
    $namaPerKodeTahun = [];
    if ($kodeUnik->isNotEmpty()) {
      $refRekening = BosRefRekening::whereIn('kode_rekening', $kodeUnik)
        ->get(['kode_rekening', 'nama_rekening', 'tahun']);
      foreach ($refRekening as $r) {
        $namaPerKodeTahun[$r->kode_rekening . '|' . $r->tahun] = $r->nama_rekening;
      }
    }

    $baris = [];
    foreach ($grup as $r) {
      $baris[] = [
        'id' => $r->id,
        'tanggal' => $r->tanggal_transaksi?->format('d-m-Y'),
        'no_bukti' => $r->no_bukti,
        'uraian' => $r->uraian,
        'kode_rekening' => $r->kode_rekening,
        'nama_rekening' => $r->kode_rekening
          ? ($namaPerKodeTahun[$r->kode_rekening . '|' . $r->tanggal_transaksi?->format('Y')] ?? null)
          : null,
        'nominal' => (float) $r->saldo,
      ];
    }

    // Meta grup (status & nomor VA) + penanda SIPLah dari
    // nota grup: modal isi grup memakai ini untuk isian
    // VA grup SIPLah (VA tidak tercatat di ARKAS).
    $metaGrup = BosSpjGrup::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    $barisNota = collect($grup)->first(fn($r) => !empty($r->id_kas_nota));
    $notaGrup = $barisNota
      ? BosKasNota::where('id_arkas', $barisNota->id_kas_nota)
        ->where('sekolah_id', $sekolahId)
        ->where('soft_delete', false)
        ->first()
      : null;

    // Status tampil grup: diturunkan dari penanda
    // selesai penerima bila grup punya penerima.
    $statusGrup = $this->statusTampilGrup($sekolahId, $kunci);

    return response()->json([
      'grup' => [
        'kunci' => $kunci,
        'manual' => $manual,
        'grup_manual_id' => $grupManualId,
        'nama_alias' => $gm?->nama,
        'jenis' => $jenis,
        'total' => array_sum(array_map(fn($r) => (float) $r->saldo, $grup)),
        'jumlah_baris' => count($grup),
        'baris_id' => $barisKonteks->id,
        'siplah' => (bool) ($notaGrup?->is_beli_di_siplah ?? false),
        'nama' => $metaGrup?->nama,
        'status' => $metaGrup?->status ?? 'belum',
        'nomor_va' => $metaGrup?->nomor_va,
        'status_tampil' => $statusGrup['status_tampil'],
        'jumlah_penerima' => $statusGrup['jumlah_penerima'],
        'jumlah_selesai' => $statusGrup['jumlah_selesai'],
      ],
      'bisa_tulis' => $this->bisaTulisAlias(),
      'baris' => $baris,
    ]);
  }

  /**
   * Gabung beberapa grup transaksi BKU menjadi satu grup
   * manual bernama alias. Seluruh anggota tiap grup yang
   * tersentuh pilihan ikut digabung (ekspansi grup utuh).
   * Penerima grup-grup asal digabung berurutan mengikuti
   * urutan grup sumber; meta digabung: status 'selesai'
   * hanya bila semua asal selesai, nomor VA dipertahankan
   * hanya bila unik, kategori manual hanya bila semua
   * asal sama.
   */
  public function grupManualGabung(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return response()->json(['message' => 'Konteks sekolah tidak ditemukan.'], 404);
    }

    $validated = $request->validate([
      'nama' => 'required|string|max:120',
      'baris_ids' => 'required|array|min:1',
      'baris_ids.*' => 'integer',
    ]);

    $idsUnik = collect($validated['baris_ids'])->map(fn($i) => (int) $i)->unique()->values();
    $terpilih = BosKasUmum::where('sekolah_id', $sekolahId)
      ->where('soft_delete', false)
      ->whereIn('id_ref_bku', self::JENIS_BELANJA)
      ->whereIn('id', $idsUnik)
      ->get();
    if ($terpilih->count() !== $idsUnik->count()) {
      return response()->json(['message' => 'Sebagian baris tidak ditemukan / bukan baris belanja.'], 422);
    }

    // Ekspansi grup utuh: tiap baris terpilih mewakili
    // SELURUH grup efektifnya (nota / bukti / baris /
    // grup manual).
    $petaTerpilih = $this->petaGrupManual($sekolahId, $terpilih->pluck('id')->all());
    $wakilSumber = [];
    foreach ($terpilih as $b) {
      $wakilSumber[$this->kunciGrupKwitansi($b, $petaTerpilih)] = $b;
    }
    if (count($wakilSumber) < 2) {
      return response()->json(['message' => 'Pilih baris dari minimal dua nota/grup berbeda untuk digabung.'], 422);
    }
    $grupSumber = []; // kunci => baris-baris grup itu
    $semua = [];      // id => baris (unik)
    foreach ($wakilSumber as $kunci => $wakil) {
      $rows = $this->barisGrupKwitansi($sekolahId, $wakil, $kunci);
      $grupSumber[$kunci] = $rows;
      foreach ($rows as $r) {
        $semua[$r->id] = $r;
      }
    }
    $semua = array_values($semua);

    // Baris SIPLah tidak ikut digabung/dipecah:
    // pengelompokannya mengikuti nota marketplace (status
    // & VA diisi inline per nota). Dicek pada seluruh
    // baris hasil ekspansi, bukan hanya yang terpilih.
    $notaIds = collect($semua)->pluck('id_kas_nota')->filter()->unique()->values();
    if ($notaIds->isNotEmpty()
      && BosKasNota::where('sekolah_id', $sekolahId)
        ->whereIn('id_arkas', $notaIds)
        ->where('is_beli_di_siplah', true)
        ->exists()) {
      return response()->json(['message' => 'Baris SIPLah tidak bisa digabung/dipecah — pengelompokannya mengikuti nota marketplace.'], 422);
    }

    // Satu tahun anggaran + satu sumber dana: tanda tangan
    // dari pemetaan anggaran tiap baris; baris tanpa
    // pemetaan memakai tahun tanggal transaksinya dengan
    // sumber kosong.
    $anggaranIds = collect($semua)->pluck('id_anggaran_arkas')->filter()->unique()->values();
    $petaAnggaran = $anggaranIds->isEmpty()
      ? collect()
      : BosAnggaran::where('sekolah_id', $sekolahId)
        ->whereIn('id_arkas', $anggaranIds)
        ->get()
        ->keyBy('id_arkas');
    $tandaTangan = [];
    foreach ($semua as $r) {
      $ag = !empty($r->id_anggaran_arkas) ? $petaAnggaran->get($r->id_anggaran_arkas) : null;
      $tandaTangan[] = $ag
        ? $ag->tahun_anggaran . '|' . $ag->id_ref_sumber_dana
        : ($r->tanggal_transaksi?->format('Y') ?? '') . '|';
    }
    if (count(array_unique($tandaTangan)) > 1) {
      return response()->json(['message' => 'Hanya transaksi satu tahun anggaran dan satu sumber dana yang bisa digabung.'], 422);
    }

    $nama = trim($validated['nama']);

    $hasil = \Illuminate\Support\Facades\DB::transaction(function () use ($sekolahId, $nama, $grupSumber, $semua) {
      $gm = BosSpjGrupManual::create(['sekolah_id' => $sekolahId, 'nama' => $nama]);
      $kunciBaru = $gm->kunci();

      // Penerima digabung: urutan mengikuti urutan grup
      // sumber (tanggal awal grup), di dalam grup urutan
      // aslinya; nomor urut ditulis ulang 1..n.
      $urutan = 0;
      foreach (array_keys($this->urutkanGrupKwitansi($grupSumber)) as $kunciSumber) {
        $penerimaSumber = BosSpjPenerima::where('sekolah_id', $sekolahId)
          ->where('grup_kunci', $kunciSumber)
          ->orderBy('urutan')
          ->orderBy('id')
          ->get();
        foreach ($penerimaSumber as $p) {
          $p->grup_kunci = $kunciBaru;
          $p->urutan = ++$urutan;
          $p->save();
        }
      }

      // Meta digabung menurut aturan di docblock method.
      $metas = BosSpjGrup::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', array_keys($grupSumber))
        ->get()
        ->keyBy('grup_kunci');
      $semuaSelesai = true;
      foreach (array_keys($grupSumber) as $kunciSumber) {
        if (($metas->get($kunciSumber)?->status ?? 'belum') !== 'selesai') {
          $semuaSelesai = false;
          break;
        }
      }
      $statusBaru = $semuaSelesai ? 'selesai' : 'belum';
      $vaUnik = $metas->map(fn($m) => $m->nomor_va)
        ->filter(fn($v) => $v !== null && trim((string) $v) !== '')
        ->unique()
        ->values();
      $vaBaru = $vaUnik->count() === 1 ? $vaUnik->first() : null;
      $katNilai = [];
      foreach (array_keys($grupSumber) as $kunciSumber) {
        $katNilai[] = $metas->get($kunciSumber)?->kategori;
      }
      $kategoriBaru = count(array_unique($katNilai, SORT_REGULAR)) === 1 ? $katNilai[0] : null;
      if ($statusBaru !== 'belum' || $vaBaru !== null || $kategoriBaru !== null) {
        BosSpjGrup::updateOrCreate(
          ['sekolah_id' => $sekolahId, 'grup_kunci' => $kunciBaru],
          ['status' => $statusBaru, 'nomor_va' => $vaBaru, 'kategori' => $kategoriBaru]
        );
      }
      BosSpjGrup::where('sekolah_id', $sekolahId)
        ->whereIn('grup_kunci', array_keys($grupSumber))
        ->delete();

      // Keanggotaan: lepas semua baris gabungan dari grup
      // manual lamanya, lalu daftarkan ke grup baru. Grup
      // manual lama yang menjadi kosong dihapus.
      $grupManualLama = collect(array_keys($grupSumber))
        ->filter(fn($k) => str_starts_with($k, 'manual:'))
        ->map(fn($k) => (int) substr($k, 7))
        ->values();
      BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
        ->whereIn('bos_kas_umum_id', array_map(fn($r) => $r->id, $semua))
        ->delete();
      $sekarang = now();
      $anggotaBaru = [];
      foreach ($semua as $r) {
        $anggotaBaru[] = [
          'grup_manual_id' => $gm->id,
          'sekolah_id' => $sekolahId,
          'bos_kas_umum_id' => $r->id,
          'created_at' => $sekarang,
          'updated_at' => $sekarang,
        ];
      }
      BosSpjGrupAnggota::insert($anggotaBaru);
      foreach ($grupManualLama as $gidLama) {
        $masihAda = BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
          ->where('grup_manual_id', $gidLama)
          ->exists();
        if (!$masihAda) {
          BosSpjGrupManual::where('sekolah_id', $sekolahId)
            ->where('id', $gidLama)
            ->delete();
        }
      }

      return [
        'grup_manual_id' => $gm->id,
        'jumlah_baris' => count($semua),
        'total' => array_sum(array_map(fn($r) => (float) $r->saldo, $semua)),
      ];
    });

    return response()->json([
      'success' => true,
      'message' => 'Grup manual "' . $nama . '" dibuat dari ' . $hasil['jumlah_baris'] . ' baris.',
      'grup_manual_id' => $hasil['grup_manual_id'],
      'jumlah_baris' => $hasil['jumlah_baris'],
      'total' => $hasil['total'],
    ]);
  }

  /**
   * Pecah satu grup transaksi BKU menjadi beberapa grup
   * manual, tiap bagian bernama alias sendiri. Pembagian
   * harus mencakup seluruh baris grup tepat satu kali.
   * Penerima & meta grup sumber diwariskan ke bagian
   * yang memuat baris pertama grup sumber (urut tanggal,
   * id).
   */
  public function grupManualPecah(Request $request)
  {
    $validated = $request->validate([
      'baris_id' => 'required|integer',
      'bagian' => 'required|array|min:2',
      'bagian.*.nama' => 'required|string|max:120',
      'bagian.*.baris_ids' => 'required|array|min:1',
      'bagian.*.baris_ids.*' => 'integer',
    ]);

    $konteks = $this->konteksGrupPenerima($request, (int) $validated['baris_id']);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunciSumber, 'grup' => $sumber] = $konteks;

    if (count($sumber) < 2) {
      return response()->json(['message' => 'Grup ini hanya berisi satu baris — tidak bisa dipecah.'], 422);
    }

    $idSumber = collect($sumber)->map(fn($r) => (int) $r->id)->sort()->values()->all();
    $idBagian = collect($validated['bagian'])
      ->flatMap(fn($bg) => collect($bg['baris_ids'])->map(fn($i) => (int) $i))
      ->sort()
      ->values()
      ->all();
    if ($idBagian !== $idSumber) {
      return response()->json(['message' => 'Pembagian harus mencakup seluruh baris grup tepat satu kali.'], 422);
    }

    $idPertama = (int) $sumber[0]->id;
    $bagian = $validated['bagian'];

    $jumlahBagian = \Illuminate\Support\Facades\DB::transaction(function () use ($sekolahId, $kunciSumber, $sumber, $bagian, $idPertama) {
      // Lepas keanggotaan lama dulu (bila sumbernya grup
      // manual) agar insert bagian tidak melanggar unik
      // bos_kas_umum_id.
      BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
        ->whereIn('bos_kas_umum_id', array_map(fn($r) => $r->id, $sumber))
        ->delete();

      $kunciPewaris = null;
      $sekarang = now();
      foreach ($bagian as $bg) {
        $gm = BosSpjGrupManual::create([
          'sekolah_id' => $sekolahId,
          'nama' => trim($bg['nama']),
        ]);
        $idsBagian = collect($bg['baris_ids'])->map(fn($i) => (int) $i)->all();
        $anggotaBaru = [];
        foreach ($idsBagian as $bid) {
          $anggotaBaru[] = [
            'grup_manual_id' => $gm->id,
            'sekolah_id' => $sekolahId,
            'bos_kas_umum_id' => $bid,
            'created_at' => $sekarang,
            'updated_at' => $sekarang,
          ];
        }
        BosSpjGrupAnggota::insert($anggotaBaru);
        if (in_array($idPertama, $idsBagian, true)) {
          $kunciPewaris = $gm->kunci();
        }
      }

      // Warisan penerima & meta sumber → bagian pewaris
      // (kunci pewaris selalu kunci manual baru, tidak
      // pernah sama dengan kunci sumber).
      if ($kunciPewaris && $kunciPewaris !== $kunciSumber) {
        BosSpjPenerima::where('sekolah_id', $sekolahId)
          ->where('grup_kunci', $kunciSumber)
          ->update(['grup_kunci' => $kunciPewaris]);
        BosSpjGrup::where('sekolah_id', $sekolahId)
          ->where('grup_kunci', $kunciSumber)
          ->update(['grup_kunci' => $kunciPewaris]);
      }

      // Grup manual sumber dibubarkan — keanggotaannya
      // sudah digantikan bagian-bagian baru.
      if (str_starts_with($kunciSumber, 'manual:')) {
        BosSpjGrupManual::where('sekolah_id', $sekolahId)
          ->where('id', (int) substr($kunciSumber, 7))
          ->delete();
      }

      return count($bagian);
    });

    return response()->json([
      'success' => true,
      'message' => 'Grup dipecah menjadi ' . $jumlahBagian . ' grup manual.',
      'jumlah_bagian' => $jumlahBagian,
    ]);
  }

  /**
   * Bubarkan satu grup manual: baris-barisnya kembali ke
   * pengelompokan bawaan ARKAS. Penerima & meta grup
   * manual dipindahkan ke grup bawaan baris pertamanya
   * (urut tanggal, id) agar pekerjaan SPJ tidak hilang.
   */
  public function grupManualBatal(Request $request)
  {
    $validated = $request->validate([
      'baris_id' => 'required|integer',
    ]);

    $konteks = $this->konteksGrupPenerima($request, (int) $validated['baris_id']);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci, 'grup' => $anggota] = $konteks;

    if (!str_starts_with($kunci, 'manual:')) {
      return response()->json(['message' => 'Grup ini bukan grup manual.'], 422);
    }
    $grupManualId = (int) substr($kunci, 7);
    $target = $this->kunciGrupKwitansi($anggota[0]);

    \Illuminate\Support\Facades\DB::transaction(function () use ($sekolahId, $kunci, $target, $grupManualId) {
      // Penerima pindah ke grup target; urutannya
      // melanjutkan urutan yang sudah ada di target.
      $urutan = (int) BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->where('grup_kunci', $target)
        ->max('urutan');
      $penerima = BosSpjPenerima::where('sekolah_id', $sekolahId)
        ->where('grup_kunci', $kunci)
        ->orderBy('urutan')
        ->orderBy('id')
        ->get();
      foreach ($penerima as $p) {
        $p->grup_kunci = $target;
        $p->urutan = ++$urutan;
        $p->save();
      }

      // Meta dipertahankan hanya bila grup target belum
      // punya meta sendiri.
      $metaManual = BosSpjGrup::where('sekolah_id', $sekolahId)
        ->where('grup_kunci', $kunci)
        ->first();
      if ($metaManual) {
        $metaTargetAda = BosSpjGrup::where('sekolah_id', $sekolahId)
          ->where('grup_kunci', $target)
          ->exists();
        if ($metaTargetAda) {
          $metaManual->delete();
        } else {
          $metaManual->grup_kunci = $target;
          $metaManual->save();
        }
      }

      BosSpjGrupAnggota::where('sekolah_id', $sekolahId)
        ->where('grup_manual_id', $grupManualId)
        ->delete();
      BosSpjGrupManual::where('sekolah_id', $sekolahId)
        ->where('id', $grupManualId)
        ->delete();
    });

    return response()->json([
      'success' => true,
      'message' => 'Grup manual dibubarkan — baris-barisnya kembali ke pengelompokan ARKAS.',
    ]);
  }

  /** Ganti nama alias satu grup manual. */
  public function grupManualUbahNama(Request $request, int $grupId)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return response()->json(['message' => 'Konteks sekolah tidak ditemukan.'], 404);
    }

    $gm = BosSpjGrupManual::where('sekolah_id', $sekolahId)->find($grupId);
    if (!$gm) {
      return response()->json(['message' => 'Grup manual tidak ditemukan di sekolah aktif.'], 404);
    }

    $validated = $request->validate([
      'nama' => 'required|string|max:120',
    ]);

    $gm->nama = trim($validated['nama']);
    $gm->save();

    return response()->json([
      'success' => true,
      'message' => 'Nama grup manual diperbarui.',
      'nama' => $gm->nama,
    ]);
  }

  /** Tambahkan penerima ke grup dari daftar PTK (snapshot data PTK saat ini). */
  public function penerimaTambahPtk(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $validated = $request->validate([
      'ptk_ids' => 'required|array|min:1',
      'ptk_ids.*' => 'integer|exists:tabel_ptk,id',
    ]);

    $sudahAda = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->whereNotNull('ptk_id')
      ->pluck('ptk_id')
      ->map(fn($v) => (int) $v)
      ->all();

    $daftarPtk = Ptk::whereIn('id', $validated['ptk_ids'])
      ->where('sekolah_id', $sekolahId)
      ->get();

    $urutan = $this->urutanBerikutnya($sekolahId, $kunci);
    $ditambahkan = 0;
    foreach ($daftarPtk as $ptk) {
      if (in_array((int) $ptk->id, $sudahAda, true)) {
        continue;
      }
      BosSpjPenerima::create([
        'sekolah_id' => $sekolahId,
        'grup_kunci' => $kunci,
        'ptk_id' => $ptk->id,
        'penerima_id' => null,
        'nama' => $ptk->nama_lengkap,
        'nip' => $ptk->nip,
        'pangkat_gol' => $ptk->pangkat_golongan,
        'nik' => $ptk->nik,
        'no_rekening' => $ptk->no_rekening,
        'nama_bank' => $ptk->nama_bank,
        'volume' => 1,
        'satuan' => 0,
        'pph_persen' => 0,
        'ppn_rp' => 0,
        'pph_rp' => 0,
        'sspd_rp' => 0,
        'admin_rp' => 0,
        'urutan' => $urutan++,
      ]);
      $ditambahkan++;
    }

    return response()->json([
      'success' => true,
      'message' => $ditambahkan > 0
        ? $ditambahkan . ' penerima dari PTK ditambahkan.'
        : 'Semua PTK terpilih sudah ada di daftar penerima grup ini.',
      'ditambahkan' => $ditambahkan,
    ]);
  }

  /** Tambahkan penerima ke grup dari induk penerima non-PTK (snapshot). */
  public function penerimaTambahMaster(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $validated = $request->validate([
      'penerima_ids' => 'required|array|min:1',
      'penerima_ids.*' => 'integer|exists:tabel_penerima,id',
    ]);

    $sudahAda = BosSpjPenerima::where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->whereNotNull('penerima_id')
      ->pluck('penerima_id')
      ->map(fn($v) => (int) $v)
      ->all();

    $daftarMaster = Penerima::whereIn('id', $validated['penerima_ids'])
      ->where('sekolah_id', $sekolahId)
      ->get();

    $urutan = $this->urutanBerikutnya($sekolahId, $kunci);
    $ditambahkan = 0;
    foreach ($daftarMaster as $master) {
      if (in_array((int) $master->id, $sudahAda, true)) {
        continue;
      }
      BosSpjPenerima::create([
        'sekolah_id' => $sekolahId,
        'grup_kunci' => $kunci,
        'ptk_id' => null,
        'penerima_id' => $master->id,
        'nama' => $master->nama,
        'nip' => $master->nip,
        'pangkat_gol' => $master->pangkat_gol,
        'nik' => $master->nik,
        'no_rekening' => $master->no_rekening,
        'nama_bank' => $master->nama_bank,
        'volume' => 1,
        'satuan' => 0,
        'pph_persen' => 0,
        'ppn_rp' => 0,
        'pph_rp' => 0,
        'sspd_rp' => 0,
        'admin_rp' => 0,
        'urutan' => $urutan++,
      ]);
      $ditambahkan++;
    }

    return response()->json([
      'success' => true,
      'message' => $ditambahkan > 0
        ? $ditambahkan . ' penerima dari tabel penerima ditambahkan.'
        : 'Semua penerima terpilih sudah ada di daftar penerima grup ini.',
      'ditambahkan' => $ditambahkan,
    ]);
  }

  /** Tambahkan penerima manual (ketikan bebas) ke grup; opsional sekalian disimpan ke induk. */
  public function penerimaSimpan(Request $request, int $id)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $validated = $request->validate($this->aturanSnapshot() + [
      'simpan_master' => 'nullable|boolean',
    ]);

    $penerimaId = null;
    if (!empty($validated['simpan_master'])) {
      $master = Penerima::create([
        'sekolah_id' => $sekolahId,
        'nama' => $validated['nama'],
        'nik' => $validated['nik'] ?? null,
        'nip' => $validated['nip'] ?? null,
        'pangkat_gol' => $validated['pangkat_gol'] ?? null,
        'alamat' => null,
        'no_rekening' => $validated['no_rekening'] ?? null,
        'nama_bank' => $validated['nama_bank'] ?? null,
      ]);
      $penerimaId = $master->id;
    }

    // Tautan sumber dari editor inline (pilihan combobox
    // PTK / tabel penerima): diverifikasi terhadap
    // sekolah ini; penerima_id dari simpan_master di
    // atas menang atas tautan.
    [$ptkId, $penerimaIdTautan] = $this->tautanSumberPenerima($sekolahId, $validated);
    $penerimaId = $penerimaId ?? $penerimaIdTautan;

    $snapshot = BosSpjPenerima::create([
      'sekolah_id' => $sekolahId,
      'grup_kunci' => $kunci,
      'ptk_id' => $ptkId,
      'penerima_id' => $penerimaId,
      'jenis' => $validated['jenis'] ?? 'orang',
      'nama' => $validated['nama'],
      'nip' => $validated['nip'] ?? null,
      'pangkat_gol' => $validated['pangkat_gol'] ?? null,
      'nik' => $validated['nik'] ?? null,
      'no_rekening' => $validated['no_rekening'] ?? null,
      'nama_bank' => $validated['nama_bank'] ?? null,
      'volume' => $validated['volume'],
      'satuan' => $validated['satuan'],
      'ppn_rp' => $validated['ppn_rp'] ?? 0,
      'pph_rp' => $validated['pph_rp'] ?? 0,
      'sspd_rp' => $validated['sspd_rp'] ?? 0,
      'admin_rp' => $validated['admin_rp'] ?? 0,
      'urutan' => $this->urutanBerikutnya($sekolahId, $kunci),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Penerima ditambahkan ke grup ini.',
      'data' => $this->snapshotKeArray($snapshot),
    ]);
  }

  /** Ubah satu snapshot penerima (harus milik grup & sekolah dari baris pada URL). */
  public function penerimaUbah(Request $request, int $id, int $penerimaId)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $snapshot = BosSpjPenerima::where('id', $penerimaId)
      ->where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    if (!$snapshot) {
      return response()->json(['message' => 'Penerima tidak ditemukan di grup ini.'], 404);
    }

    $validated = $request->validate($this->aturanSnapshot());

    // Tautan sumber mengikuti isian editor inline saat
    // ini (combobox): mengganti nama bebas melepaskan
    // tautan, memilih sumber memasangnya kembali.
    [$ptkId, $penerimaId] = $this->tautanSumberPenerima($sekolahId, $validated);

    $snapshot->update([
      'jenis' => $validated['jenis'] ?? 'orang',
      'ptk_id' => $ptkId,
      'penerima_id' => $penerimaId,
      'nama' => $validated['nama'],
      'nip' => $validated['nip'] ?? null,
      'pangkat_gol' => $validated['pangkat_gol'] ?? null,
      'nik' => $validated['nik'] ?? null,
      'no_rekening' => $validated['no_rekening'] ?? null,
      'nama_bank' => $validated['nama_bank'] ?? null,
      'volume' => $validated['volume'],
      'satuan' => $validated['satuan'],
      'ppn_rp' => $validated['ppn_rp'] ?? 0,
      'pph_rp' => $validated['pph_rp'] ?? 0,
      'sspd_rp' => $validated['sspd_rp'] ?? 0,
      'admin_rp' => $validated['admin_rp'] ?? 0,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Penerima diperbarui.',
      'data' => $this->snapshotKeArray($snapshot->fresh()),
    ]);
  }

  /**
   * Tandai selesai / belum selesai satu snapshot
   * penerima. Status tampil grup diturunkan dari
   * penanda ini (lihat statusTampilGrup) dan ikut
   * dikembalikan agar klien bisa memperbarui lencana
   * grup tanpa memuat ulang.
   */
  public function penerimaSelesaiUbah(Request $request, int $id, int $penerimaId)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $snapshot = BosSpjPenerima::where('id', $penerimaId)
      ->where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    if (!$snapshot) {
      return response()->json(['message' => 'Penerima tidak ditemukan di grup ini.'], 404);
    }

    $validated = $request->validate([
      'selesai' => 'required|boolean',
    ]);

    $snapshot->update(['selesai' => (bool) $validated['selesai']]);

    $statusGrup = $this->statusTampilGrup($sekolahId, $kunci);

    return response()->json([
      'success' => true,
      'message' => 'Status penerima diperbarui.',
      'data' => $this->snapshotKeArray($snapshot->fresh()),
      'status_tampil' => $statusGrup['status_tampil'],
      'jumlah_penerima' => $statusGrup['jumlah_penerima'],
      'jumlah_selesai' => $statusGrup['jumlah_selesai'],
    ]);
  }

  /** Hapus satu snapshot penerima dari grupnya. */
  public function penerimaHapus(Request $request, int $id, int $penerimaId)
  {
    $konteks = $this->konteksGrupPenerima($request, $id);
    if (!$konteks) {
      return response()->json(['message' => 'Baris BKU belanja tidak ditemukan di sekolah aktif.'], 404);
    }
    ['sekolahId' => $sekolahId, 'kunci' => $kunci] = $konteks;

    $snapshot = BosSpjPenerima::where('id', $penerimaId)
      ->where('sekolah_id', $sekolahId)
      ->where('grup_kunci', $kunci)
      ->first();
    if (!$snapshot) {
      return response()->json(['message' => 'Penerima tidak ditemukan di grup ini.'], 404);
    }

    $snapshot->delete();

    return response()->json([
      'success' => true,
      'message' => 'Penerima dihapus dari grup ini.',
    ]);
  }

  /** Buat induk penerima non-PTK baru (konteks sekolah dari baris BKU pada URL). */
  public function penerimaMasterSimpan(Request $request, int $id)
  {
    $sekolahId = $this->sekolahDariBaris($request, $id);
    if (!$sekolahId) {
      return response()->json(['message' => 'Baris BKU tidak ditemukan di sekolah aktif.'], 404);
    }

    $validated = $request->validate($this->aturanMaster());

    $master = Penerima::create([
      'sekolah_id' => $sekolahId,
      'nama' => $validated['nama'],
      'jenis' => $validated['jenis'] ?? 'orang',
      'nik' => $validated['nik'] ?? null,
      'nip' => $validated['nip'] ?? null,
      'pangkat_gol' => $validated['pangkat_gol'] ?? null,
      'pemilik' => $validated['pemilik'] ?? null,
      'npwp' => $validated['npwp'] ?? null,
      'no_hp' => $validated['no_hp'] ?? null,
      'alamat' => $validated['alamat'] ?? null,
      'no_rekening' => $validated['no_rekening'] ?? null,
      'nama_bank' => $validated['nama_bank'] ?? null,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Penerima baru disimpan ke tabel penerima.',
      'data' => [
        'id' => $master->id,
        'nama' => $master->nama,
        'jenis' => $master->jenis ?: 'orang',
        'nik' => $master->nik,
        'nip' => $master->nip,
        'pangkat_gol' => $master->pangkat_gol,
        'pemilik' => $master->pemilik,
        'npwp' => $master->npwp,
        'no_hp' => $master->no_hp,
        'alamat' => $master->alamat,
        'no_rekening' => $master->no_rekening,
        'nama_bank' => $master->nama_bank,
      ],
    ]);
  }

  /** Ubah induk penerima non-PTK (harus milik sekolah dari baris pada URL). */
  public function penerimaMasterUbah(Request $request, int $id, int $masterId)
  {
    $sekolahId = $this->sekolahDariBaris($request, $id);
    if (!$sekolahId) {
      return response()->json(['message' => 'Baris BKU tidak ditemukan di sekolah aktif.'], 404);
    }

    $master = Penerima::where('id', $masterId)
      ->where('sekolah_id', $sekolahId)
      ->first();
    if (!$master) {
      return response()->json(['message' => 'Penerima tidak ditemukan di tabel penerima sekolah ini.'], 404);
    }

    $validated = $request->validate($this->aturanMaster());

    $master->update([
      'nama' => $validated['nama'],
      'jenis' => $validated['jenis'] ?? ($master->jenis ?: 'orang'),
      'nik' => $validated['nik'] ?? null,
      'nip' => $validated['nip'] ?? null,
      'pangkat_gol' => $validated['pangkat_gol'] ?? null,
      'pemilik' => $validated['pemilik'] ?? null,
      'npwp' => $validated['npwp'] ?? null,
      'no_hp' => $validated['no_hp'] ?? null,
      'alamat' => $validated['alamat'] ?? null,
      'no_rekening' => $validated['no_rekening'] ?? null,
      'nama_bank' => $validated['nama_bank'] ?? null,
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Data penerima di tabel penerima diperbarui.',
      'data' => [
        'id' => $master->id,
        'nama' => $master->nama,
        'jenis' => $master->jenis ?: 'orang',
        'nik' => $master->nik,
        'nip' => $master->nip,
        'pangkat_gol' => $master->pangkat_gol,
        'pemilik' => $master->pemilik,
        'npwp' => $master->npwp,
        'no_hp' => $master->no_hp,
        'alamat' => $master->alamat,
        'no_rekening' => $master->no_rekening,
        'nama_bank' => $master->nama_bank,
      ],
    ]);
  }

  /** Hapus induk penerima non-PTK (snapshot di grup tidak ikut terhapus). */
  public function penerimaMasterHapus(Request $request, int $id, int $masterId)
  {
    $sekolahId = $this->sekolahDariBaris($request, $id);
    if (!$sekolahId) {
      return response()->json(['message' => 'Baris BKU tidak ditemukan di sekolah aktif.'], 404);
    }

    $master = Penerima::where('id', $masterId)
      ->where('sekolah_id', $sekolahId)
      ->first();
    if (!$master) {
      return response()->json(['message' => 'Penerima tidak ditemukan di tabel penerima sekolah ini.'], 404);
    }

    $master->delete();

    return response()->json([
      'success' => true,
      'message' => 'Penerima dihapus dari tabel penerima.',
    ]);
  }

  // ── Token sinkron (Fase 1.12, lapisan kerja) ──────────
  //
  // Token adalah kunci agen sinkron di laptop ARKAS.
  // Yang disimpan di database hanya hash sha256-nya;
  // token asli ditampilkan SEKALI sesudah dibuat (lewat
  // flash session) dan tidak bisa dilihat lagi — lupa
  // berarti buat ulang, dan pembuatan ulang mencabut
  // token lama seketika. Pembuatan di sini persis
  // meniru perintah `bos:token` (Str::random(48) +
  // updateOrCreate hash + terakhir_dipakai direset).

  /** Halaman status token agen sinkron sekolah ini. */
  public function token(Request $request)
  {
    $konteks = $this->konteksUmum($request);
    $sekolahId = $konteks['sekolahId'];

    $tokenBaris = $sekolahId
      ? BosSinkronToken::where('sekolah_id', $sekolahId)->first()
      : null;

    return view('content.kelola-bos.token', $konteks + [
      'tokenBaris' => $tokenBaris,
    ]);
  }

  /**
   * Buat / buat ulang token. Token asli hanya hidup di
   * flash session satu kali tampil; tidak pernah
   * ditulis ke database, log, atau tempat lain.
   */
  public function tokenBuat(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Konteks sekolah belum dipilih — token tidak dibuat.');
    }

    $token = Str::random(48);

    BosSinkronToken::updateOrCreate(
      ['sekolah_id' => $sekolahId],
      ['token_hash' => hash('sha256', $token), 'terakhir_dipakai' => null]
    );

    return redirect()->route('kelola-bos.token')
      ->with('token_baru', $token)
      ->with('success', 'Token sinkron baru dibuat untuk sekolah ini. Salin dan simpan sekarang — token tidak akan ditampilkan lagi.');
  }
}
