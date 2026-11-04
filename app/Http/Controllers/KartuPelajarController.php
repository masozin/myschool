<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Rombel;
use App\Models\SekolahLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;
use chillerlan\QRCode\{QRCode, QROptions};
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Common\EccLevel;

class KartuPelajarController extends Controller
{
  // Daftar tema yang tersedia — dipakai juga untuk validasi & dropdown di view
  public static array $temaList = [
    'navy-gold'      => 'Navy Gold — Klasik & Formal',
    'emerald-modern' => 'Emerald Modern — Segar & Modern',
    'maroon-elegant' => 'Maroon Elegant — Elegan & Berwibawa',
  ];

  public static array $temaWarna = [
    'navy-gold' => [
      'primary'   => '#0a3c6e',
      'primary_dark' => '#062847',
      'accent'    => '#d4af37',
      'text_muted' => '#4a5b70',
    ],
    'emerald-modern' => [
      'primary'   => '#0a6958',
      'primary_dark' => '#054235',
      'accent'    => '#c4a859',
      'text_muted' => '#3f5c53',
    ],
    'maroon-elegant' => [
      'primary'   => '#801a2c',
      'primary_dark' => '#4a0c19',
      'accent'    => '#c9a254',
      'text_muted' => '#6b3540',
    ],
  ];

  protected array $bulanIndo = [
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

  public function index()
  {
    $rombels = Rombel::orderBy('nama_rombel')->get();
    $temaList = self::$temaList;
    return view('content.kartu-pelajar.index', compact('rombels', 'temaList'));
  }

  public function data(Request $request)
  {
    $query = Siswa::query()
      ->whereHas('registrasiTerbaru', fn($q) => $q->where('status', 'aktif'));

    if ($request->filled('rombel_id')) {
      $query->whereHas('rombels', fn($q) => $q->where('tabel_rombel_anggota.rombel_id', $request->rombel_id));
    }
    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(fn($q) => $q->where('nama_siswa', 'like', "%{$search}%")
        ->orWhere('nisn', 'like', "%{$search}%"));
    }

    return response()->json(
      $query->orderBy('nama_siswa')->get(['id', 'nama_siswa', 'nisn', 'jenis_kelamin', 'photo'])
    );
  }

  public function cetak(Request $request)
  {
    $request->validate([
      'siswa_ids'   => 'required|array|min:1',
      'siswa_ids.*' => 'exists:tabel_siswa,id',
      'sisi'        => 'required|in:1,2',
      'tema'        => 'required|in:' . implode(',', array_keys(self::$temaList)),
    ]);



    $siswaList = Siswa::with([
      'rombels' => fn($q) => $q->orderByDesc('tabel_rombel_anggota.id'),
      'registrasiTerbaru',
    ])
      ->whereIn('id', $request->siswa_ids)
      ->orderBy('nama_siswa')
      ->get();

    $barcodeGenerator = new BarcodeGeneratorPNG();

    $qrOptions = new QROptions([
      'outputInterface'   => QRGdImagePNG::class,  // ganti dari 'outputType' => OUTPUT_IMAGE_PNG
      'eccLevel'          => EccLevel::M,           // ganti dari QRCode::ECC_M
      'scale'             => 6,
      'imageTransparent'  => false,
      'imageBase64'       => true,
    ]);
    $qrGenerator = new QRCode($qrOptions);

    foreach ($siswaList as $siswa) {
      $siswa->jk_label = $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
      $siswa->ttl_label = trim(
        ($siswa->tempat_lahir ?? '-') . ', ' .
          ($siswa->tgl_lahir
            ? $siswa->tgl_lahir->day . ' ' . $this->bulanIndo[$siswa->tgl_lahir->month] . ' ' . $siswa->tgl_lahir->year
            : '-')
      );
      $siswa->nis_nisn_label = ($siswa->registrasiTerbaru->nis ?? '-') . ' / ' . ($siswa->nisn ?? '-');

      // ⬇️ tambahan: generate barcode dari NISN
      if ($siswa->nisn) {
        $barcodePng = $barcodeGenerator->getBarcode($siswa->nisn, $barcodeGenerator::TYPE_CODE_128, 2, 40);
        $siswa->barcode_base64 = 'data:image/png;base64,' . base64_encode($barcodePng);
      } else {
        $siswa->barcode_base64 = null;
      }

      $qrData = 'NISN:' . ($siswa->nisn ?? $siswa->id);
      $siswa->qrcode_base64 = $qrGenerator->render($qrData);
    }

    $sekolah = null;
    if (Auth::check() && Auth::user()->ptk) {
      $sekolah = Auth::user()->ptk->sekolah;
    }

    // load relasi wilayah dari detailTerbaru
    $detail = $sekolah->detailTerbaru()->with(['village', 'district', 'regency', 'province'])->first();
    $alamatLengkap = collect([
      $detail->alamat_sekolah ?? null,
      $detail->village->name ?? null,
      $detail->district->name ?? null,
      $detail->regency->name ?? null,
      $detail->province->name ?? null,
    ])->filter()
      ->map(fn($v) => $this->formatTitleCaseKecualiRomawi($v))
      ->implode(', ');

    $sekolahGlobal = [
      'id'             => $sekolah->id ?? null,
      'nama_sekolah'   => $sekolah->detailTerbaru->nama_sekolah ?? '-',
      'npsn'           => $sekolah->npsn ?? null,
      'kepala_sekolah' => $sekolah->detailTerbaru->kepala_sekolah ?? '-',
      'nip_kepsek'     => $sekolah->detailTerbaru->nip ?? '-',
      'alamat'         => $alamatLengkap,
      'no_telp'        => $sekolah->detailTerbaru->no_telp ?? '-',
      'email'          => $sekolah->detailTerbaru->email ?? '-',
      'website'        => $sekolah->detailTerbaru->site ?? '-',
    ];


    // Ambil logo: sekolah -> kabupaten -> null (placeholder ditangani di view)
    $logos = SekolahLogo::where('sekolah_id', $sekolah->id ?? 0)
      ->whereIn('tipe', [
        SekolahLogo::TIPE_LOGO_SEKOLAH,
        SekolahLogo::TIPE_LOGO_KABUPATEN,
        SekolahLogo::TIPE_TTD_KS,
        SekolahLogo::TIPE_STEMPEL,
      ])
      ->get()->keyBy('tipe');

    $logoPath = null;
    if ($l = $logos->get(SekolahLogo::TIPE_LOGO_SEKOLAH)) {
      if (file_exists($l->absolute_path)) $logoPath = $l->absolute_path;
    }
    if (!$logoPath && ($l = $logos->get(SekolahLogo::TIPE_LOGO_KABUPATEN))) {
      if (file_exists($l->absolute_path)) $logoPath = $l->absolute_path;
    }

    $ttdKsPath = null;
    if ($l = $logos->get(SekolahLogo::TIPE_TTD_KS)) {
      if (file_exists($l->absolute_path)) $ttdKsPath = $l->absolute_path;
    }

    $stempelPath = null;
    if ($l = $logos->get(SekolahLogo::TIPE_STEMPEL)) {
      if (file_exists($l->absolute_path)) $stempelPath = $l->absolute_path;
    }

    $tema = $request->tema;
    $bgDepan   = public_path("assets/img/kartu/{$tema}-depan.png");
    $bgBelakang = public_path("assets/img/kartu/{$tema}-belakang.png");
    $warna      = self::$temaWarna[$tema];
    $sisi = $request->sisi;

    $pdf = Pdf::setPaper('a4', 'portrait');
    $pdf->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);

    $pdf->loadView('content.kartu-pelajar.cetak', compact(
      'siswaList',
      'sekolahGlobal',
      'logoPath',
      'ttdKsPath',
      'stempelPath',
      'sisi',
      'bgDepan',
      'bgBelakang',
      'warna'
    ));

    return $pdf->stream('kartu-pelajar.pdf');
  }

  /**
   * Ubah teks jadi Title Case per kata, kecuali kata yang berupa angka romawi
   * (I, II, III, IV, ..., XX, dst) akan tetap UPPERCASE penuh.
   */
  protected function formatTitleCaseKecualiRomawi(string $text): string
  {
    $words = explode(' ', strtolower($text));

    $result = array_map(function ($word) {
      // Bersihkan tanda baca di ujung kata untuk deteksi romawi (misal "iv," atau "iv.")
      $bare = trim($word, '.,()');

      if ($bare !== '' && $this->isRomanNumeral($bare)) {
        return str_replace($bare, strtoupper($bare), $word);
      }

      return ucfirst($word);
    }, $words);

    return implode(' ', $result);
  }

  /**
   * Cek apakah sebuah string adalah angka romawi valid (I, II, III, IV, V, ... XXXIX, dst)
   */
  protected function isRomanNumeral(string $str): bool
  {
    $str = strtoupper($str);
    return (bool) preg_match(
      '/^M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})(IX|IV|V?I{0,3})$/',
      $str
    ) && $str !== ''; // regex kosong juga match, jadi wajib cek tidak kosong
  }
}
