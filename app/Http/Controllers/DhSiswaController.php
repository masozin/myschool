<?php

namespace App\Http\Controllers;

use App\Models\KalenderPendidikan;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\SekolahDetail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DhSiswaController extends Controller
{
  private const NAMA_BULAN = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
  ];

  // Sama seperti WARNA_JENIS_PDF di KalenderPendidikanController — sengaja
  // di-duplikasi (bukan di-share) supaya controller ini berdiri sendiri.
  private const WARNA_JENIS_PDF = [
    'Libur Nasional'          => ['solid' => '#dc3545', 'bg' => '#f8d7da'],
    'Libur Semester'          => ['solid' => '#ffc107', 'bg' => '#fff3cd'],
    'Penilaian'               => ['solid' => '#0dcaf0', 'bg' => '#cff4fc'],
    'Libur Puasa dan Lebaran' => ['solid' => '#6c757d', 'bg' => '#e2e3e5'],
    'Pembagian Rapor'         => ['solid' => '#696cff', 'bg' => '#e7e7fd'],
    'Lainnya'                 => ['solid' => '#212529', 'bg' => '#e9ecef'],
  ];

  private const HARI_1_HURUF = ['M', 'S', 'S', 'R', 'K', 'J', 'S']; // 0=Minggu .. 6=Sabtu

  /**
   * Halaman pilih Kelas + Bulan sebelum download.
   */
  public function index()
  {
    $rombelList = Rombel::with('semester')->orderBy('nama_rombel')->get();

    return view('content.dh-siswa.index', [
      'rombelList' => $rombelList,
    ]);
  }

  /**
   * GET /daftar-hadir/cetak?rombel_id=..&bulan=7&tahun=2025&libur_mingguan=0
   */
  public function cetak(Request $request)
  {
    $request->validate([
      'rombel_id'      => ['required', 'exists:tabel_rombel,id'],
      'bulan'          => ['required', 'integer', 'between:1,12'],
      'tahun'          => ['required', 'integer'],
      'libur_mingguan' => ['nullable', 'string'],
    ]);

    $rombel = Rombel::with(['semester', 'ptk'])->findOrFail($request->rombel_id);
    $bulan = (int) $request->bulan;
    $tahun = (int) $request->tahun;

    $liburMingguan = collect(explode(',', $request->input('libur_mingguan', '0')))
      ->map(fn($d) => (int) $d)
      ->filter(fn($d) => $d >= 0 && $d <= 6)
      ->unique()
      ->values()
      ->all();

    if (empty($liburMingguan)) {
      $liburMingguan = [0];
    }

    $sekolahDetail = SekolahDetail::where('sekolah_id', $rombel->sekolah_id)
      ->where('semester_id', $rombel->semester_id)
      ->first();

    $daftarSiswa = RombelAnggota::with('siswa.registrasiTerbaru')
      ->where('rombel_id', $rombel->id)
      ->get()
      ->map(fn($a) => $a->siswa)
      ->filter()
      ->sortBy('nama_siswa')
      ->values();

    $awal = Carbon::create($tahun, $bulan, 1);
    $akhir = $awal->copy()->endOfMonth();
    $jumlahHari = $akhir->day;
    $tahunPelajaran = $rombel->semester->tahun_pelajaran ?? null;

    $kegiatanData = $tahunPelajaran
      ? KalenderPendidikan::where('tahun_pelajaran', $tahunPelajaran)
      ->orderBy('tanggal_mulai')
      ->get()
      : collect();

    // Peta tanggal -> jenis (untuk pewarnaan kolom tanggal)
    $petaJenis = [];
    foreach ($kegiatanData as $k) {
      $mulai = $k->tanggal_mulai->copy();
      $selesai = ($k->tanggal_selesai ?? $k->tanggal_mulai)->copy();
      if ($selesai->lt($awal) || $mulai->gt($akhir)) {
        continue;
      }
      $mulaiClip = $mulai->lt($awal) ? $awal->copy() : $mulai;
      $selesaiClip = $selesai->gt($akhir) ? $akhir->copy() : $selesai;
      for ($d = $mulaiClip->day; $d <= $selesaiClip->day; $d++) {
        $petaJenis[$d] ??= $k->jenis;
      }
    }

    // Bangun definisi tiap kolom tanggal: angka, huruf hari, & warna kolom.
    // Prioritas warna: kegiatan kalender pendidikan > libur mingguan > default.
    $kolomTanggal = [];
    for ($d = 1; $d <= $jumlahHari; $d++) {
      $tglObj = Carbon::create($tahun, $bulan, $d);
      $dow = $tglObj->dayOfWeek;
      $isLM = in_array($dow, $liburMingguan, true);
      $jenis = $petaJenis[$d] ?? null;

      $warnaBg = null;
      if ($jenis && isset(self::WARNA_JENIS_PDF[$jenis])) {
        $warnaBg = self::WARNA_JENIS_PDF[$jenis]['bg'];
      } elseif ($isLM) {
        $warnaBg = '#f8d7da';
      }

      $kolomTanggal[] = [
        'angka'    => $d,
        'huruf'    => self::HARI_1_HURUF[$dow],
        'warna_bg' => $warnaBg,
      ];
    }

    // Daftar kegiatan bulan ini (tanggal di-clip ke batas bulan) untuk
    // daftar keterangan di bawah tabel.
    $daftarKegiatan = $kegiatanData->map(function ($k) use ($awal, $akhir) {
      $mulai = $k->tanggal_mulai->copy();
      $selesai = ($k->tanggal_selesai ?? $k->tanggal_mulai)->copy();
      if ($selesai->lt($awal) || $mulai->gt($akhir)) {
        return null;
      }
      $mulaiClip = $mulai->lt($awal) ? $awal->copy() : $mulai;
      $selesaiClip = $selesai->gt($akhir) ? $akhir->copy() : $selesai;

      return [
        'label'    => $mulaiClip->day === $selesaiClip->day
          ? (string) $mulaiClip->day
          : "{$mulaiClip->day}-{$selesaiClip->day}",
        'teks'     => $k->kegiatan ?: $k->jenis,
        'jenis'    => $k->jenis,
        'is_libur' => (bool) $k->is_libur,
      ];
    })->filter()->sortBy(fn($k) => (int) $k['label'])->values();

    $lebarKolomTanggal = round(50 / max(count($kolomTanggal), 1), 3);

    $pdf = Pdf::loadView('content.dh-siswa.cetak', [
      'namaSekolah'       => $sekolahDetail->nama_sekolah ?? '-',
      'tahunPelajaran'    => $tahunPelajaran ?? '-',
      'namaRombel'        => $rombel->nama_rombel,
      'namaBulan'         => self::NAMA_BULAN[$bulan],
      'tahun'             => $tahun,
      'kolomTanggal'      => $kolomTanggal,
      'lebarKolomTanggal' => $lebarKolomTanggal,
      'daftarSiswa'       => $daftarSiswa,
      'daftarKegiatan'    => $daftarKegiatan,
      'warnaJenis'        => self::WARNA_JENIS_PDF,
      'kepalaSekolah'     => $sekolahDetail->kepala_sekolah ?? '-',
      'nipKepsek'         => $sekolahDetail->nip ?? '-',
      'waliKelas'         => $rombel->ptk->nama_ptk ?? '-',
      'nipWaliKelas'      => $rombel->ptk->nip ?? '-',
      'tanggalTtd'        => $akhir->day . ' ' . self::NAMA_BULAN[$bulan] . ' ' . $tahun,
    ])
      // dompdf 'folio' portrait = 612 x 936pt = 21.6cm x 33cm persis.
      // Landscape membalikkannya jadi 33cm x 21.6cm sesuai permintaan.
      ->setPaper('folio', 'landscape');

    return $pdf->download("daftar-hadir-{$rombel->nama_rombel}-{$bulan}-{$tahun}.pdf");
  }
}
