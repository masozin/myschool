<?php

namespace App\Http\Controllers;

use App\Imports\KalenderPendidikanImport;
use App\Models\KalenderPendidikan;
use App\Models\SekolahDetail;
use App\Models\SekolahLogo;
use App\Models\Semester;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class KalenderPendidikanController extends Controller
{
  use AuthorizesRequests;

  /**
   * Daftar jenis kegiatan yang diperbolehkan (dropdown "Jenis" di tabel).
   */
  public const JENIS_KEGIATAN = [
    'Libur Nasional',
    'Libur Semester',
    'Penilaian',
    'Libur Puasa dan Lebaran',
    'Pembagian Rapor',
    'Lainnya',
  ];

  /**
   * Warna per jenis kegiatan khusus untuk PDF (hex solid untuk bulatan/teks,
   * hex muda untuk background sel tanggal). Dompdf tidak mengenal CSS variable
   * atau kelas Bootstrap, jadi hex ditulis eksplisit di sini — nilainya sengaja
   * disamakan dengan warna bg-label-*bg-* yang dipakai di tampilan layar.
   */
  public const WARNA_JENIS_PDF = [
    'Libur Nasional'         => ['solid' => '#dc3545', 'bg' => '#f8d7da'],
    'Libur Semester'         => ['solid' => '#ffc107', 'bg' => '#fff3cd'],
    'Penilaian'               => ['solid' => '#0dcaf0', 'bg' => '#cff4fc'],
    'Libur Puasa dan Lebaran' => ['solid' => '#6c757d', 'bg' => '#e2e3e5'],
    'Pembagian Rapor'         => ['solid' => '#696cff', 'bg' => '#e7e7fd'],
    'Lainnya'                 => ['solid' => '#212529', 'bg' => '#e9ecef'],
  ];

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

  /**
   * Tampilkan halaman utama.
   * Daftar tahun pelajaran untuk filter = gabungan tiga sumber,
   * terurut menaik:
   *  - tahun yang benar-benar ada di tabel_semester (termasuk
   *    tahun sesi login — sumber lama hanya memakai jam dinding,
   *    jadi tahun sesi yang lampau tidak pernah muncul);
   *  - tahun yang sudah memiliki data kalender;
   *  - jendela berjalan (tahun lalu s/d +2) supaya kalender
   *    tahun ajaran depan tetap bisa disiapkan meski Semester
   *    untuk tahun itu belum dibuat.
   * Tahun pelajaran sesi login ditandai untuk dipilih otomatis.
   */
  public function index()
  {
    $dariSemester = Semester::query()->distinct()->pluck('tahun_pelajaran');
    $dariData     = KalenderPendidikan::query()->distinct()->pluck('tahun_pelajaran');
    $jendela      = collect(range(now()->year - 1, now()->year + 2))
      ->map(fn($t) => $t . '/' . ($t + 1));

    $daftarTahunPelajaran = $dariSemester
      ->merge($dariData)
      ->merge($jendela)
      ->filter()
      ->unique()
      ->sort()
      ->values();

    $semesterSesi = Semester::find(session('semester_id'));

    return view('content.kalender-pendidikan.index', [
      'daftarTahunPelajaran' => $daftarTahunPelajaran,
      'jenisKegiatan'        => self::JENIS_KEGIATAN,
      'tahunPelajaranSesi'   => $semesterSesi?->tahun_pelajaran,
    ]);
  }

  /**
   * GET /kalender-pendidikan/tabel
   */
  public function tabel(Request $request)
  {
    $query = KalenderPendidikan::orderBy('tanggal_mulai');

    if ($request->filled('tahun_pelajaran')) {
      $query->where('tahun_pelajaran', $request->tahun_pelajaran);
    }

    $data = $query->get()->map(function ($k) {
      return [
        'id'              => $k->id,
        'tahun_pelajaran' => $k->tahun_pelajaran,
        'jenis_semester'  => $k->jenis_semester,
        'jenis'           => $k->jenis,
        'tanggal_mulai'   => $k->tanggal_mulai->format('Y-m-d'),
        'tanggal_selesai' => $k->tanggal_selesai
          ? $k->tanggal_selesai->format('Y-m-d')
          : $k->tanggal_mulai->format('Y-m-d'),
        'is_libur'        => (bool) $k->is_libur,
        'kegiatan'        => $k->kegiatan,
        'can_edit'        => auth()->user()?->can('update', $k) ?? false,
        'can_delete'      => auth()->user()?->can('delete', $k) ?? false,
      ];
    });

    return response()->json(['data' => $data]);
  }

  /**
   * POST /kalender-pendidikan
   */
  public function store(Request $request)
  {
    $this->authorize('create', KalenderPendidikan::class);

    $validated = $this->validated($request);
    $kalender = KalenderPendidikan::create($validated);

    return response()->json([
      'message' => 'Kegiatan kalender berhasil ditambahkan',
      'data'    => $kalender,
    ]);
  }

  /**
   * PUT /kalender-pendidikan/{kalenderPendidikan}
   */
  public function update(Request $request, KalenderPendidikan $kalenderPendidikan)
  {
    $this->authorize('update', $kalenderPendidikan);

    $validated = $this->validated($request);
    $kalenderPendidikan->update($validated);

    return response()->json([
      'message' => 'Kegiatan kalender berhasil diperbarui',
    ]);
  }

  /**
   * DELETE /kalender-pendidikan/{kalenderPendidikan}
   */
  public function destroy(KalenderPendidikan $kalenderPendidikan)
  {
    $this->authorize('delete', $kalenderPendidikan);

    $kalenderPendidikan->delete();

    return response()->json([
      'message' => 'Kegiatan kalender berhasil dihapus',
    ]);
  }

  /**
   * GET /kalender-pendidikan/import/template
   * Unduh template Excel untuk impor: tajuk + contoh terisi +
   * daftar pilihan pada kolom Jenis dan Libur.
   */
  public function downloadTemplateImport()
  {
    $this->authorize('create', KalenderPendidikan::class);

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Kalender Pendidikan');

    $headers = ['Tanggal Mulai', 'Tanggal Selesai', 'Jenis', 'Libur', 'Kegiatan'];
    foreach ($headers as $i => $label) {
      $sheet->setCellValue([$i + 1, 1], $label);
    }
    $sheet->getStyle('A1:E1')->applyFromArray([
      'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF'], 'size' => 10],
      'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FF1A56A4']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);

    $contoh = [
      ['2025-07-14', '2025-07-18', 'Lainnya', 'Tidak', 'Masa Pengenalan Lingkungan Sekolah (MPLS)'],
      ['2025-08-17', '2025-08-17', 'Libur Nasional', 'Ya', 'Hari Kemerdekaan RI'],
      ['2025-12-08', '2025-12-12', 'Penilaian', 'Tidak', 'Asesmen Sumatif Akhir Semester'],
      ['2025-12-20', '2025-12-20', 'Pembagian Rapor', 'Tidak', 'Pembagian rapor semester ganjil'],
    ];
    foreach ($contoh as $r => $baris) {
      foreach ($baris as $c => $nilai) {
        $sheet->setCellValue([$c + 1, $r + 2], $nilai);
      }
      $sheet->getComment('A' . ($r + 2))->getText()
        ->createTextRun('Baris contoh — boleh dihapus sebelum mengimpor.');
    }
    $sheet->getStyle('A2:B200')->getNumberFormat()->setFormatCode('yyyy-mm-dd');

    // Daftar pilihan untuk kolom Jenis (C) & Libur (D)
    $validasiJenis = new DataValidation();
    $validasiJenis->setType(DataValidation::TYPE_LIST)
      ->setAllowBlank(true)
      ->setShowDropDown(false)
      ->setFormula1('"' . implode(',', self::JENIS_KEGIATAN) . '"')
      // Isian di luar daftar DITOLAK di tempat dengan pesan yang
      // menyebut pilihan yang sah (bukan sekadar panah dropdown)
      ->setShowErrorMessage(true)
      ->setErrorStyle(DataValidation::STYLE_STOP)
      ->setErrorTitle('Jenis tidak dikenal')
      ->setError('Jenis harus salah satu dari: ' . implode(', ', self::JENIS_KEGIATAN) . '.')
      ->setShowInputMessage(true)
      ->setPromptTitle('Pilih jenis kegiatan')
      ->setPrompt('Pilihan yang bisa dimasukkan: ' . implode(', ', self::JENIS_KEGIATAN) . '.');
    $sheet->setDataValidation('C2:C200', $validasiJenis);

    $validasiLibur = new DataValidation();
    $validasiLibur->setType(DataValidation::TYPE_LIST)
      ->setAllowBlank(true)
      ->setShowDropDown(false)
      ->setFormula1('"Ya,Tidak"')
      ->setShowErrorMessage(true)
      ->setErrorStyle(DataValidation::STYLE_STOP)
      ->setErrorTitle('Isian Libur tidak dikenal')
      ->setError('Kolom Libur hanya boleh diisi Ya atau Tidak.');
    $sheet->setDataValidation('D2:D200', $validasiLibur);

    // Format bersyarat: tanggal mulai MELEBIHI tanggal selesai
    // (dan tanggal selesai terisi) → kedua sel tanggal merah,
    // supaya kekeliruan rentang terlihat sebelum file diimpor
    foreach (['A2:A200', 'B2:B200'] as $rentangSel) {
      $bersyarat = new Conditional();
      $bersyarat->setConditionType(Conditional::CONDITION_EXPRESSION);
      $bersyarat->addCondition('AND($B2<>"",$A2>$B2)');
      $bersyarat->getStyle()->applyFromArray([
        'font' => ['color' => ['rgb' => 'FF9D1B29'], 'bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFF8D7DA']],
      ]);
      $sheet->getStyle($rentangSel)->setConditionalStyles([$bersyarat]);
    }

    $sheet->getColumnDimension('A')->setWidth(14);
    $sheet->getColumnDimension('B')->setWidth(14);
    $sheet->getColumnDimension('C')->setWidth(24);
    $sheet->getColumnDimension('D')->setWidth(9);
    $sheet->getColumnDimension('E')->setWidth(52);
    $sheet->freezePane('A2');

    $petunjuk = $spreadsheet->createSheet();
    $petunjuk->setTitle('Petunjuk');
    $barisPetunjuk = [
      'PETUNJUK IMPOR KALENDER PENDIDIKAN',
      '',
      '1. Tahun pelajaran TIDAK ditulis di file — diambil dari filter tahun yang aktif di halaman saat mengimpor.',
      '2. Semua tanggal harus berada di dalam rentang tahun pelajaran itu (1 Juli tahun awal s/d 30 Juni tahun akhir).',
      '3. Tanggal Selesai boleh dikosongkan bila kegiatan hanya satu hari.',
      '4. Jenis harus salah satu dari: ' . implode(', ', self::JENIS_KEGIATAN) . '.',
      '5. Kolom Libur diisi Ya atau Tidak.',
      '6. Baris yang sama persis dengan data yang sudah ada akan dilewati (tidak diduplikat).',
      '7. Baris contoh pada lembar pertama boleh dihapus sebelum mengimpor.',
    ];
    foreach ($barisPetunjuk as $i => $teks) {
      $petunjuk->setCellValue([1, $i + 1], $teks);
    }
    $petunjuk->getColumnDimension('A')->setWidth(120);

    $spreadsheet->setActiveSheetIndex(0);

    $writer = new XlsxWriter($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, 'template-impor-kalender-pendidikan.xlsx', [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }

  /**
   * POST /kalender-pendidikan/import
   * Tahun pelajaran dari filter aktif (dikirim form), bukan dari file.
   */
  public function import(Request $request)
  {
    $this->authorize('create', KalenderPendidikan::class);

    $request->validate([
      'file_excel'      => 'required|file|mimes:xlsx,xls|max:5120',
      'tahun_pelajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
    ]);

    $import = new KalenderPendidikanImport($request->tahun_pelajaran);
    Excel::import($import, $request->file('file_excel'));

    return response()->json([
      'message'   => "Impor selesai. {$import->berhasil} kegiatan ditambahkan, {$import->dilewati} dilewati (duplikat).",
      'berhasil'  => $import->berhasil,
      'dilewati'  => $import->dilewati,
      'errors'    => $import->errors,
    ]);
  }

  /**
   * GET /kalender-pendidikan/cetak?tahun_pelajaran=2025/2026
   * Download PDF A4, 2 halaman: halaman 1 = Semester Ganjil (Jul-Des),
   * halaman 2 = Semester Genap (Jan-Jun).
   */
  public function cetak(Request $request)
  {
    $request->validate([
      'tahun_pelajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
    ]);

    $tahunPelajaran = $request->tahun_pelajaran;
    [$tahunAwal, $tahunAkhir] = array_map('intval', explode('/', $tahunPelajaran));

    $data = KalenderPendidikan::where('tahun_pelajaran', $tahunPelajaran)
      ->orderBy('tanggal_mulai')
      ->get();

    $bulanGanjil = collect(range(7, 12))
      ->map(fn($b) => $this->bangunBulan($tahunAwal, $b, $data))
      ->all();

    $bulanGenap = collect(range(1, 6))
      ->map(fn($b) => $this->bangunBulan($tahunAkhir, $b, $data))
      ->all();

    $pdf = Pdf::loadView('content.kalender-pendidikan.cetak', [
      'tahunPelajaran' => $tahunPelajaran,
      'bulanGanjil'    => $bulanGanjil,
      'bulanGenap'     => $bulanGenap,
      'warnaJenis'     => self::WARNA_JENIS_PDF,
    ])->setPaper('a4', 'portrait');

    return $pdf->download("kalender-pendidikan-{$tahunAwal}-{$tahunAkhir}.pdf");
  }

  /**
   * Susun data 1 bulan untuk kebutuhan cetak: grid mingguan (Minggu-Sabtu)
   * + daftar kegiatan bulan itu (tanggal sudah di-clip ke batas bulan,
   * sama seperti logika di JS untuk tampilan kalender ringkasan layar).
   */
  private function bangunBulan(int $tahun, int $bulan, \Illuminate\Support\Collection $data): array
  {
    $awal = Carbon::create($tahun, $bulan, 1);
    $akhir = $awal->copy()->endOfMonth();

    $kegiatanBulanIni = $data->map(function ($k) use ($awal, $akhir) {
      $mulai = $k->tanggal_mulai->copy();
      $selesai = ($k->tanggal_selesai ?? $k->tanggal_mulai)->copy();

      if ($selesai->lt($awal) || $mulai->gt($akhir)) {
        return null;
      }

      $mulaiClip = $mulai->lt($awal) ? $awal->copy() : $mulai;
      $selesaiClip = $selesai->gt($akhir) ? $akhir->copy() : $selesai;

      return (object) [
        'jenis'        => $k->jenis,
        'kegiatan'     => $k->kegiatan,
        'is_libur'     => (bool) $k->is_libur,
        'hari_mulai'   => (int) $mulaiClip->day,
        'hari_selesai' => (int) $selesaiClip->day,
      ];
    })->filter()->sortBy('hari_mulai')->values();

    // Peta tanggal -> jenis (dipakai buat pewarnaan sel grid mingguan)
    $petaHari = [];
    foreach ($kegiatanBulanIni as $keg) {
      for ($d = $keg->hari_mulai; $d <= $keg->hari_selesai; $d++) {
        $petaHari[$d] ??= $keg->jenis;
      }
    }

    // Susun sel-sel jadi baris per minggu, 7 kolom (Minggu-Sabtu)
    $sel = array_fill(0, $awal->dayOfWeek, null); // offset hari pertama
    for ($d = 1; $d <= $akhir->day; $d++) {
      $sel[] = ['angka' => $d, 'jenis' => $petaHari[$d] ?? null];
    }
    while (count($sel) % 7 !== 0) {
      $sel[] = null;
    }

    $daftarKegiatan = $kegiatanBulanIni->map(fn($keg) => [
      'label'    => $keg->hari_mulai === $keg->hari_selesai
        ? (string) $keg->hari_mulai
        : $keg->hari_mulai . "\u{2013}" . $keg->hari_selesai,
      'teks'     => $keg->kegiatan ?: $keg->jenis,
      'jenis'    => $keg->jenis,
      'is_libur' => $keg->is_libur,
    ])->values()->all();

    return [
      'label'    => self::NAMA_BULAN[$bulan] . ' ' . $tahun,
      'minggu'   => array_chunk($sel, 7),
      'kegiatan' => $daftarKegiatan,
    ];
  }

  /**
   * GET /kalender-pendidikan/cetak-tabel?tahun_pelajaran=2025/2026&libur_mingguan=0
   * Download PDF Model 2 (format tabel resmi): 2 halaman, halaman 1 Semester
   * Ganjil, halaman 2 Semester Genap. Tiap semester = 1 tabel panjang berisi
   * 6 blok bulan (masing-masing 6 baris kalender + 1 baris Jumlah), ditutup
   * 2 baris rekap di akhir tabel (Jumlah Hari Semester & Persentase).
   *
   * `libur_mingguan` = daftar angka hari (0=Minggu .. 6=Sabtu, mengikuti
   * Carbon::dayOfWeek) dipisah koma, dikirim dari modal pilihan di frontend.
   */
  public function cetakTabel(Request $request)
  {
    $request->validate([
      'tahun_pelajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
      'libur_mingguan'  => ['nullable', 'string'],
      'tempat'          => ['nullable', 'string', 'max:100'],
    ]);

    $tahunPelajaran = $request->tahun_pelajaran;
    [$tahunAwal, $tahunAkhir] = array_map('intval', explode('/', $tahunPelajaran));

    $liburMingguan = collect(explode(',', $request->input('libur_mingguan', '0')))
      ->map(fn($d) => (int) $d)
      ->filter(fn($d) => $d >= 0 && $d <= 6)
      ->unique()
      ->values()
      ->all();

    if (empty($liburMingguan)) {
      $liburMingguan = [0]; // fallback aman: Minggu
    }

    $data = KalenderPendidikan::where('tahun_pelajaran', $tahunPelajaran)
      ->orderBy('tanggal_mulai')
      ->get();

    $bulanGanjil = collect(range(7, 12))
      ->map(fn($b) => $this->bangunBulanTabel($tahunAwal, $b, $data, $liburMingguan))
      ->all();

    $bulanGenap = collect(range(1, 6))
      ->map(fn($b) => $this->bangunBulanTabel($tahunAkhir, $b, $data, $liburMingguan))
      ->all();

    // Kop surat: pakai gambar kop potrait yang sudah diupload sekolah kalau
    // ada. Kalau belum ada, view akan menampilkan kotak penanda "KOP SEKOLAH"
    // sebagai gantinya (lihat cetak-tabel.blade.php).
    $sekolahId = Auth::user()?->ptk?->sekolah_id;

    $kopLogo = SekolahLogo::where('sekolah_id', $sekolahId)
      ->where('tipe', SekolahLogo::TIPE_KOP_POTRAIT)
      ->first();

    // Kepala Sekolah untuk TTD: ambil dari data sekolah terbaru, TIDAK
    // dikunci ke semester tertentu (konsisten dengan keputusan sebelumnya
    // untuk melepas Kalender Pendidikan dari siklus hidup Semester).
    $sekolahDetail = SekolahDetail::where('sekolah_id', $sekolahId)
      ->latest()
      ->first();

    // Titimangsa (tempat & tanggal TTD): tempat dari input modal (opsional,
    // kalau kosong ditampilkan titik-titik di view untuk ditulis manual).
    // Tanggal pakai tanggal saat PDF dibuat.
    $tempatTtd = $request->filled('tempat') ? trim($request->tempat) : null;
    $sekarang = Carbon::now();
    $tanggalTtd = $sekarang->day . ' ' . self::NAMA_BULAN[$sekarang->month] . ' ' . $sekarang->year;

    $pdf = Pdf::loadView('content.kalender-pendidikan.cetak-tabel', [
      'tahunPelajaran' => $tahunPelajaran,
      'bulanGanjil'    => $bulanGanjil,
      'bulanGenap'     => $bulanGenap,
      'warnaJenis'     => self::WARNA_JENIS_PDF,
      'totalGanjil'    => $this->totalSemesterTabel($bulanGanjil),
      'totalGenap'     => $this->totalSemesterTabel($bulanGenap),
      'kopUrl'         => $kopLogo?->absolute_path,
      'kepalaSekolah'  => $sekolahDetail->kepala_sekolah ?? '-',
      'nipKepsek'      => $sekolahDetail->nip ?? '-',
      'tempatTtd'      => $tempatTtd,
      'tanggalTtd'     => $tanggalTtd,
    ])->setPaper('a4', 'portrait');

    return $pdf->download("kalender-pendidikan-tabel-{$tahunAwal}-{$tahunAkhir}.pdf");
  }

  /**
   * Jumlahkan LM/Libur/Efektif/Jumlah dari 6 bulan jadi total 1 semester.
   */
  private function totalSemesterTabel(array $bulanList): array
  {
    $total = ['lm' => 0, 'libur' => 0, 'efektif' => 0, 'jumlah' => 0];

    foreach ($bulanList as $b) {
      $total['lm']      += $b['total']['lm'];
      $total['libur']   += $b['total']['libur'];
      $total['efektif'] += $b['total']['efektif'];
      $total['jumlah']  += $b['total']['jumlah'];
    }

    return $total;
  }

  /**
   * Susun data 1 bulan untuk format tabel (Model 2): 6 baris minggu
   * (dipaksa selalu 6 baris, minggu ke-6 boleh kosong kalau bulannya
   * cuma butuh 5 baris) + hitungan LM/Libur/Efektif/Jumlah per minggu.
   *
   * Prioritas warna tanggal: kalau tanggal itu masuk rentang kegiatan
   * tertentu -> warna sesuai jenis kegiatan. Kalau tidak, tapi hari itu
   * termasuk libur mingguan terpilih -> merah. Selain itu -> warna default.
   *
   * LM   = jumlah hari dalam minggu itu yang merupakan hari libur mingguan.
   * Libur = jumlah hari libur (dari data kegiatan is_libur) SELAIN yang
   *         sudah dihitung sebagai LM (tidak dobel hitung).
   * Efektif = Jumlah - LM - Libur.
   * Jumlah = jumlah tanggal pada minggu itu yang benar-benar ada di bulan
   *          ini (sel kosong di awal/akhir tidak dihitung).
   */
  private function bangunBulanTabel(int $tahun, int $bulan, \Illuminate\Support\Collection $data, array $liburMingguan): array
  {
    $awal = Carbon::create($tahun, $bulan, 1);
    $akhir = $awal->copy()->endOfMonth();

    $kegiatanBulanIni = $data->map(function ($k) use ($awal, $akhir) {
      $mulai = $k->tanggal_mulai->copy();
      $selesai = ($k->tanggal_selesai ?? $k->tanggal_mulai)->copy();

      if ($selesai->lt($awal) || $mulai->gt($akhir)) {
        return null;
      }

      $mulaiClip = $mulai->lt($awal) ? $awal->copy() : $mulai;
      $selesaiClip = $selesai->gt($akhir) ? $akhir->copy() : $selesai;

      return (object) [
        'jenis'        => $k->jenis,
        'kegiatan'     => $k->kegiatan,
        'is_libur'     => (bool) $k->is_libur,
        'hari_mulai'   => (int) $mulaiClip->day,
        'hari_selesai' => (int) $selesaiClip->day,
      ];
    })->filter()->sortBy('hari_mulai')->values();

    $petaJenis = [];
    $petaLiburKegiatan = [];
    foreach ($kegiatanBulanIni as $keg) {
      for ($d = $keg->hari_mulai; $d <= $keg->hari_selesai; $d++) {
        $petaJenis[$d] ??= $keg->jenis;
        if ($keg->is_libur) {
          $petaLiburKegiatan[$d] = true;
        }
      }
    }

    // Susun 42 sel (6 minggu x 7 hari), dipaksa selalu 6 baris
    $offset = $awal->dayOfWeek; // 0 = Minggu
    $selDatar = array_fill(0, $offset, null);
    for ($d = 1; $d <= $akhir->day; $d++) {
      $selDatar[] = $d;
    }
    while (count($selDatar) < 42) {
      $selDatar[] = null;
    }
    $selDatar = array_slice($selDatar, 0, 42);

    $totalBulan = ['lm' => 0, 'libur' => 0, 'efektif' => 0, 'jumlah' => 0];
    $barisMinggu = [];

    foreach (array_chunk($selDatar, 7) as $minggu) {
      $lm = 0;
      $libur = 0;
      $jumlah = 0;
      $selRender = [];

      foreach ($minggu as $hariIdx => $tgl) {
        if ($tgl === null) {
          $selRender[] = null;
          continue;
        }

        $jumlah++;
        $isLiburMingguan = in_array($hariIdx, $liburMingguan, true);
        $isLiburKegiatan = $petaLiburKegiatan[$tgl] ?? false;

        if ($isLiburMingguan) {
          $lm++;
        } elseif ($isLiburKegiatan) {
          $libur++;
        }

        $selRender[] = [
          'angka'             => $tgl,
          'jenis'             => $petaJenis[$tgl] ?? null,
          'is_libur_mingguan' => $isLiburMingguan,
        ];
      }

      $efektif = $jumlah - $lm - $libur;

      $totalBulan['lm']      += $lm;
      $totalBulan['libur']   += $libur;
      $totalBulan['efektif'] += $efektif;
      $totalBulan['jumlah']  += $jumlah;

      $barisMinggu[] = [
        'sel'     => $selRender,
        'lm'      => $lm,
        'libur'   => $libur,
        'efektif' => $efektif,
        'jumlah'  => $jumlah,
      ];
    }

    $daftarKegiatan = $kegiatanBulanIni->map(fn($keg) => [
      'tgl'        => $keg->hari_mulai === $keg->hari_selesai
        ? (string) $keg->hari_mulai
        : "{$keg->hari_mulai}-{$keg->hari_selesai}",
      'keterangan' => $keg->kegiatan ?: $keg->jenis,
      'jenis'      => $keg->jenis,
      'is_libur'   => $keg->is_libur,
    ])->values()->all();

    return [
      'label'       => self::NAMA_BULAN[$bulan],
      'tahun'       => $tahun,
      'minggu'      => $barisMinggu,
      'total'       => $totalBulan,
      'kegiatan'    => $daftarKegiatan,
      // Bulan tetap minimal 7 baris (6 kalender + 1 Jumlah), tapi kalau
      // kegiatan bulan itu lebih dari 7, tabel diperpanjang supaya tiap
      // kegiatan dapat barisnya sendiri (tidak di-marge jadi 1 sel).
      'baris_total' => max(7, count($daftarKegiatan)),
    ];
  }

  /**
   * Validasi request tambah/ubah.
   *
   * `tahun_pelajaran` WAJIB dikirim dari dropdown filter aktif di frontend
   * (format "2025/2026"), dipakai untuk mengunci rentang tanggal yang boleh
   * diinput (1 Juli tahun awal - 30 Juni tahun akhir).
   *
   * `jenis_semester` TIDAK diminta dari user — dihitung otomatis dari
   * tanggal_mulai (Juli-Desember = Ganjil, Januari-Juni = Genap), konsisten
   * dengan konvensi yang dulu dipakai Semester::fromTanggal().
   */
  private function validated(Request $request): array
  {
    $validated = $request->validate([
      'jenis'           => ['required', 'string', Rule::in(self::JENIS_KEGIATAN)],
      'tanggal_mulai'   => 'required|date',
      'tanggal_selesai' => 'nullable|date|after_or_equal:tanggal_mulai',
      'is_libur'        => 'nullable|boolean',
      'kegiatan'        => 'nullable|string',
      'tahun_pelajaran' => ['required', 'string', 'regex:/^\d{4}\/\d{4}$/'],
    ]);

    $validated['tanggal_selesai'] = $validated['tanggal_selesai'] ?? $validated['tanggal_mulai'];
    $validated['is_libur'] = $request->boolean('is_libur');

    [$tahunAwal, $tahunAkhir] = array_map('intval', explode('/', $validated['tahun_pelajaran']));

    $rentangMulai   = Carbon::create($tahunAwal, 7, 1)->format('Y-m-d');
    $rentangSelesai = Carbon::create($tahunAkhir, 6, 30)->format('Y-m-d');

    if ($validated['tanggal_mulai'] < $rentangMulai || $validated['tanggal_selesai'] > $rentangSelesai) {
      throw ValidationException::withMessages([
        'tanggal_mulai' => "Tanggal harus berada dalam rentang tahun pelajaran {$validated['tahun_pelajaran']} "
          . '(' . Carbon::parse($rentangMulai)->format('d/m/Y') . ' - '
          . Carbon::parse($rentangSelesai)->format('d/m/Y') . ').',
      ]);
    }

    // Hitung otomatis jenis_semester dari tanggal_mulai (bukan input user).
    $bulanMulai = Carbon::parse($validated['tanggal_mulai'])->month;
    $validated['jenis_semester'] = $bulanMulai >= 7 ? 'Ganjil' : 'Genap';

    return $validated;
  }
}
