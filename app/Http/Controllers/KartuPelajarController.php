<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Rombel;
use App\Models\Sekolah;
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

  /**
   * Sekolah konteks pengguna — pola yang sama dengan SKHU/SKL.
   * Peran berkait sekolah memakai sekolah PTK-nya. Admin tidak punya
   * PTK, jadi konteksnya dipilih lewat parameter ?sekolah_id=
   * (disimpan di sesi 'admin_sekolah_id') atau — bila hanya ada satu
   * sekolah — sekolah satu-satunya itu. Null hanya untuk Admin yang
   * belum memilih di antara beberapa sekolah; peran lain tanpa
   * tautan sekolah menerima 403.
   */
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

    $sekolahId = $user->ptk?->sekolah_id;
    abort_if(!$sekolahId, 403, 'Akun Anda belum terhubung ke sekolah.');

    return (int) $sekolahId;
  }

  public function index()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    $temaList = self::$temaList;

    // Rombel dibatasi sekolah konteks + semester sesi (dulu: semua
    // rombel dari semua sekolah dan semester tercampur).
    $rombels = $sekolahId
      ? Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', session('semester_id'))
        ->orderBy('nama_rombel')
        ->get()
      : collect();

    $daftarSekolah = Auth::user()->hasRole('Admin')
      ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
      : collect();

    return view('content.kartu-pelajar.index', compact('rombels', 'temaList', 'sekolahId', 'daftarSekolah'));
  }

  public function data(Request $request)
  {
    $sekolahId = $this->sekolahKonteksIdNullable();

    if (!$sekolahId) {
      return response()->json([]);
    }

    $query = Siswa::query()
      ->whereHas('registrasiTerbaru', fn($q) => $q->where('status', 'aktif')->where('sekolah_id', $sekolahId));

    if ($request->filled('rombel_id')) {
      // Rombel saringan harus milik sekolah konteks pada semester
      // sesi; rombel sekolah/semester lain dianggap tidak ada.
      $rombelValid = Rombel::where('id', $request->rombel_id)
        ->where('sekolah_id', $sekolahId)
        ->where('semester_id', session('semester_id'))
        ->exists();

      if (!$rombelValid) {
        return response()->json([]);
      }

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

    $sekolahId = $this->sekolahKonteksIdNullable();

    if (!$sekolahId) {
      return back()->with('error', 'Pilih sekolah dulu lewat pemilih konteks sekolah di halaman Kartu Pelajar.');
    }

    // Hanya siswa sekolah konteks yang dicetak — id siswa sekolah
    // lain yang ikut terkirim disaring keluar.
    $siswaList = Siswa::with([
      'rombels' => fn($q) => $q->orderByDesc('tabel_rombel_anggota.id'),
      'registrasiTerbaru',
    ])
      ->whereIn('id', $request->siswa_ids)
      ->whereHas('registrasiTerbaru', fn($q) => $q->where('sekolah_id', $sekolahId))
      ->orderBy('nama_siswa')
      ->get();

    if ($siswaList->isEmpty()) {
      return back()->with('error', 'Tidak ada siswa terpilih yang termasuk sekolah konteks Anda.');
    }

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

    // Sekolah dari konteks (PTK pengguna / pilihan Admin), bukan
    // lagi hanya dari PTK — Admin tanpa PTK dulu error di sini
    // karena $sekolah bernilai null.
    $sekolah = Sekolah::find($sekolahId);

    // load relasi wilayah dari detailTerbaru
    $detail = $sekolah?->detailTerbaru()->with(['village', 'district', 'regency', 'province'])->first();
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
