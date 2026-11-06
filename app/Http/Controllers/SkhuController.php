<?php

namespace App\Http\Controllers;

use App\Imports\SkhuImport;
use App\Models\MapelMapping;
use App\Models\MapelKelompok;
use App\Models\Rombel;
use App\Models\RombelAnggota;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\Skhu;
use App\Models\SkhuNilai;
use App\Models\SkhuTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

class SkhuController extends Controller
{
  // Tingkat akhir per jenjang
  private array $tingkatAkhir = [
    'SD'  => 6,
    'SMP' => 9,
    'SMA' => 12,
    'SMK' => 12,
  ];

  // ── Halaman utama SKHU ────────────────────────────────────────────────
  public function index()
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::with(['detailTerbaru', 'asets'])->findOrFail($this->sekolahKonteksId());
    $semester = Semester::find(session('semester_id'));

    if (!$semester) {
      return back()->with('error', 'Semester aktif tidak ditemukan.');
    }

    $jenjang      = strtoupper($sekolah->jenjang);
    $tingkatAkhir = $this->tingkatAkhir[$jenjang] ?? null;

    if (!$tingkatAkhir) {
      return back()->with('error', 'Jenjang sekolah tidak dikenali.');
    }

    // Ambil siswa kelas akhir dari rombel aktif semester ini
    $siswaAkhir = $this->getSiswaKelasAkhir($sekolah->id, $semester->id, $tingkatAkhir);

    // Guru hanya melihat siswa pada rombel yang ia wali kelasi —
    // guru lain tidak diberi akses ke SKHU siswa tersebut
    if ($user->hasRole('Guru')) {
      $siswaAkhir = $siswaAkhir
        ->filter(fn($anggota) => (int) ($anggota->rombel->ptk_id ?? 0) === (int) $user->ptk?->id)
        ->values();
    }

    // Template SKHU untuk jenjang + semester ini
    $template = SkhuTemplate::aktifUntuk($jenjang, $semester->id);

    // Mapel yang berlaku untuk jenjang + tingkat akhir + semester ini
    $mapels = $this->getMapels($jenjang, $tingkatAkhir, $semester->id, $sekolah->id);

    return view('content.skhu.index', compact(
      'sekolah',
      'semester',
      'siswaAkhir',
      'template',
      'mapels',
      'jenjang',
      'tingkatAkhir'
    ));
  }

  // ── Ambil data siswa kelas akhir ──────────────────────────────────────
  private function getSiswaKelasAkhir(int $sekolahId, int $semesterId, int $tingkatAkhir)
  {
    return RombelAnggota::with([
      'siswa.registrasiTerbaru',
      'siswa.skhu' => function ($q) use ($semesterId) {
        $q->where('semester_id', $semesterId);
      },
      'rombel',
    ])
      ->whereHas('siswa', fn($q) => $q->whereHas('registrasiTerbaru', fn($qr) => $qr->whereIn('status', ['aktif', 'lulus'])))
      ->whereHas('rombel', function ($q) use ($sekolahId, $semesterId, $tingkatAkhir) {
        $q->where('sekolah_id', $sekolahId)
          ->where('semester_id', $semesterId)
          ->where('tingkat', $tingkatAkhir);
      })
      ->get()
      ->sortBy('siswa.nama_siswa');
  }

  /**
   * Ambil daftar MapelMapping yang berlaku untuk SKHU.
   *
   * Filter:
   *   - semester_id  : sesuai semester aktif
   *   - jenjang      : jenjang sekolah
   *   - tingkat      : tingkat akhir jenjang
   *   - rumus berlaku: scope berlakuUntuk() di MapelMapping
   *     (satu-satunya sumber; kurikulum per siswa dari rombelnya)
   *
   * Return: Collection of MapelMapping, sudah eager-load masterMapel & kelompok,
   *         diurutkan kelompok.urutan → mapelMapping.urutan.
   */
  private function getMapels(string $jenjang, int $tingkat, int $semesterId, int $sekolahId, ?int $kurikulumId = null)
  {
    return MapelMapping::with(['masterMapel.kelompok', 'kelompok'])
      ->berlakuUntuk($sekolahId, $jenjang, $tingkat, $semesterId, $kurikulumId)
      ->get()
      ->sortBy(fn($m) => [
        $m->kelompokTampil()?->urutan ?? 999,
        $m->urutan ?? 999,
      ]);
  }

  /**
   * Sekolah konteks dari PTK pengguna; akun tanpa tautan
   * sekolah (mis. Admin murni) menerima pesan yang jelas,
   * bukan error.
   */
  private function sekolahKonteksId(): int
  {
    $sekolahId = Auth::user()->ptk?->sekolah_id;
    abort_if(!$sekolahId, 403, 'Akun Anda belum terhubung ke sekolah.');

    return (int) $sekolahId;
  }

  /**
   * Guru hanya boleh mengelola SKHU siswa di rombel yang ia
   * wali kelasi pada semester itu.
   */
  private function abortUnlessWaliKelas(int $siswaId, int $sekolahId, int $semesterId): void
  {
    $user = Auth::user();

    $punya = RombelAnggota::where('siswa_id', $siswaId)
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->whereHas('rombel', fn($q) => $q->where('ptk_id', $user->ptk?->id))
      ->exists();

    abort_unless($punya, 403, 'Anda bukan wali kelas siswa ini.');
  }

  // ── Form input/edit SKHU per siswa ────────────────────────────────────
  public function formSiswa(int $siswaId)
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::with(['detailTerbaru', 'asets'])
      ->findOrFail($this->sekolahKonteksId());
    $semester = Semester::findOrFail(session('semester_id'));
    $jenjang  = strtoupper($sekolah->jenjang);

    // Validasi siswa memang berada di kelas akhir sekolah ini
    $anggota = RombelAnggota::with(['siswa.registrasiTerbaru', 'rombel'])
      ->whereHas('siswa', fn($q) => $q->whereHas('registrasiTerbaru', fn($qr) => $qr->whereIn('status', ['aktif', 'lulus'])))
      ->whereHas('rombel', function ($q) use ($sekolah, $semester, $jenjang) {
        $q->where('sekolah_id', $sekolah->id)
          ->where('semester_id', $semester->id)
          ->where('tingkat', $this->tingkatAkhir[$jenjang]);
      })
      ->whereHas('siswa', fn($q) => $q->where('id', $siswaId))
      ->firstOrFail();

    // Guru hanya boleh membuka siswa rombelnya sendiri
    if ($user->hasRole('Guru') && (int) $anggota->rombel->ptk_id !== (int) $user->ptk?->id) {
      abort(403, 'Anda bukan wali kelas siswa ini.');
    }

    $siswa    = $anggota->siswa;
    $template = SkhuTemplate::aktifUntuk($jenjang, $semester->id);

    // Mapel dikelompokkan untuk tampilan form
    $mapels = $this->getMapels(
        $jenjang,
        $this->tingkatAkhir[$jenjang],
        $semester->id,
        $sekolah->id,
        $anggota->rombel?->kurikulum_id !== null ? (int) $anggota->rombel->kurikulum_id : null
      )
      ->groupBy(fn($m) => $m->kelompokTampil()?->nama ?? 'Lainnya');

    // SKHU yang sudah ada (jika edit)
    $skhu = Skhu::with('nilais.mapelMapping')
      ->where('siswa_id', $siswaId)
      ->where('semester_id', $semester->id)
      ->first();

    // Map nilai yang sudah ada ke array [mapel_mapping_id => nilai]
    $nilaiExisting = $skhu
      ? $skhu->nilais->pluck('nilai', 'mapel_mapping_id')->toArray()
      : [];

    return view('content.skhu.form-skhu', compact(
      'siswa',
      'skhu',
      'sekolah',
      'semester',
      'template',
      'mapels',
      'nilaiExisting',
      'anggota'
    ));
  }

  // =========================================================================
  // SIMPAN NILAI SKHU SISWA (draft / final)
  // =========================================================================
  public function simpan(Request $request, int $siswaId)
  {
    $request->validate([
      'nomor_surat'           => 'nullable|string|max:100',
      'nomor_ujian'           => 'nullable|string|max:100',
      'tanggal_mulai_ujian'   => 'nullable|date',
      'tanggal_selesai_ujian' => 'nullable|date',
      'tanggal_terbit'        => 'nullable|date',
      'nilai'                 => 'required|array',
      'nilai.*'               => 'numeric|min:0|max:100',
      'finalisasi'            => 'nullable|boolean',
    ]);

    $siswa    = \App\Models\Siswa::findOrFail($siswaId);
    $semester = Semester::where('id', session('semester_id'))->first()
      ?? Semester::where('is_active', 1)->first();

    $sekolahId = Auth::user()->ptk->sekolah_id
      ?? $siswa->registrasiTerbaru?->sekolah_id;

    // Guru hanya boleh menyimpan SKHU siswa rombelnya sendiri
    if (Auth::user()->hasRole('Guru')) {
      $this->abortUnlessWaliKelas($siswa->id, (int) $sekolahId, (int) $semester->id);
    }

    // Cari atau buat SKHU
    $skhu = Skhu::firstOrNew([
      'siswa_id'    => $siswa->id,
      'semester_id' => $semester->id,
    ]);

    // Jika sudah final, tolak perubahan
    if ($skhu->exists && $skhu->status === 'final') {
      return response()->json([
        'message' => 'SKHU sudah difinalisasi dan tidak dapat diubah. Batalkan finalisasi terlebih dahulu.',
      ], 422);
    }

    $skhu->fill([
      'sekolah_id'            => $sekolahId,
      'nomor_surat'           => $request->nomor_surat,
      'nomor_ujian'           => $request->nomor_ujian,
      'tanggal_mulai_ujian'   => $request->tanggal_mulai_ujian,
      'tanggal_selesai_ujian' => $request->tanggal_selesai_ujian,
      'tanggal_terbit'        => $request->tanggal_terbit,
      'status'                => 'draft',
      'generated_at'          => now(),
    ]);
    $skhu->save();

    // Hanya mapping yang memang berlaku untuk siswa ini yang
    // boleh disimpan nilainya (kurikulum dari rombel siswa)
    $anggotaSiswa = RombelAnggota::where('siswa_id', $siswa->id)
      ->where('semester_id', $semester->id)
      ->where('sekolah_id', $sekolahId)
      ->with('rombel')
      ->first();
    $jenjangSiswa  = strtoupper(Sekolah::find($sekolahId)?->jenjang ?? '');
    $mapelsBerlaku = isset($this->tingkatAkhir[$jenjangSiswa])
      ? $this->getMapels(
          $jenjangSiswa,
          $this->tingkatAkhir[$jenjangSiswa],
          (int) $semester->id,
          (int) $sekolahId,
          $anggotaSiswa?->rombel?->kurikulum_id !== null ? (int) $anggotaSiswa->rombel->kurikulum_id : null
        )->pluck('id')->map(fn($id) => (int) $id)->all()
      : [];

    // Simpan nilai — key dari request adalah mapel_mapping_id
    foreach ($request->nilai as $mapelMappingId => $nilai) {
      if (!in_array((int) $mapelMappingId, $mapelsBerlaku, true)) {
        continue;
      }

      SkhuNilai::updateOrCreate(
        ['skhu_id' => $skhu->id, 'mapel_mapping_id' => $mapelMappingId],
        ['nilai'   => $nilai]
      );
    }

    $pesan = 'Draft SKHU berhasil disimpan.';

    return response()->json(['message' => $pesan, 'skhu_id' => $skhu->id]);
  }

  // ── Toggle status finalisasi per siswa ───────────────────────────────
  public function UpdateStatus(int $skhuId)
  {
    $user = Auth::user();

    // Batasi ke sekolah konteks — sebelumnya SKHU milik sekolah
    // lain pun bisa diubah statusnya hanya dengan id-nya
    $skhu = Skhu::where('sekolah_id', $user->ptk->sekolah_id)->findOrFail($skhuId);

    // Guru hanya boleh mengubah SKHU siswa rombelnya sendiri
    if ($user->hasRole('Guru')) {
      $this->abortUnlessWaliKelas((int) $skhu->siswa_id, (int) $skhu->sekolah_id, (int) $skhu->semester_id);
    }

    if ($skhu->status === 'final') {
      $skhu->update(['status' => 'draft']);
      $pesan = 'SKHU berhasil dibatalkan finalisasinya.';
    } else {
      // Validasi kelengkapan sebelum finalisasi
      $errors = $skhu->validasiFinalisasi();

      if (!empty($errors)) {
        return response()->json([
          'message' => 'SKHU belum bisa difinalisasi, data belum lengkap.',
          'errors'  => $errors,
        ], 422);
      }

      $skhu->update(['status' => 'final']);
      $pesan = 'SKHU berhasil difinalisasi.';
    }

    return response()->json(['message' => $pesan]);
  }

  // =========================================================================
  // FINALISASI SEMUA SKHU (dari halaman index)
  // =========================================================================
  public function finalisasiSemua(Request $request)
  {
    // Finalisasi massal berdampak ke seluruh sekolah — khusus Operator
    if (!Auth::user()->hasRole('Operator Sekolah')) {
      return response()->json(['message' => 'Akses ditolak. Finalisasi massal hanya untuk Operator Sekolah.'], 403);
    }

    $sekolahId = Auth::user()->ptk->sekolah_id;
    $semester  = Semester::where('id', session('semester_id'))->first()
      ?? Semester::where('is_active', 1)->first();

    $skhuList = Skhu::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semester->id)
      ->where('status', 'draft')
      ->get();

    if ($skhuList->isEmpty()) {
      return response()->json(['message' => 'Tidak ada SKHU berstatus Draft untuk difinalisasi.'], 422);
    }

    // Pisahkan yang lolos validasi & yang tidak
    $validIds   = [];
    $gagalList  = [];

    foreach ($skhuList as $skhu) {
      $errors = $skhu->validasiFinalisasi();

      if (empty($errors)) {
        $validIds[] = $skhu->id;
      } else {
        $gagalList[] = [
          'id'     => $skhu->id,
          'nama'   => $skhu->siswa->nama_siswa ?? "SKHU #{$skhu->id}", // sesuaikan kolom nama siswa
          'errors' => $errors,
        ];
      }
    }

    if (!empty($validIds)) {
      Skhu::whereIn('id', $validIds)->update(['status' => 'final']);
    }

    $jumlahBerhasil = count($validIds);
    $jumlahGagal    = count($gagalList);

    return response()->json([
      'message'         => "{$jumlahBerhasil} SKHU berhasil difinalisasi" .
        ($jumlahGagal > 0 ? ", {$jumlahGagal} SKHU gagal (data belum lengkap)." : '.'),
      'jumlah_berhasil' => $jumlahBerhasil,
      'jumlah_gagal'    => $jumlahGagal,
      'gagal'           => $gagalList,
    ]);
  }

  // ── Generate PDF per siswa ────────────────────────────────────────────
  public function generatePdf(int $skhuId)
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::findOrFail($user->ptk->sekolah_id);
    $semester = Semester::findOrFail(session('semester_id'));
    $jenjang  = strtoupper($sekolah->jenjang);

    $skhu = Skhu::with([
      'siswa.registrasiTerbaru',
      'sekolah.detailTerbaru',
      'sekolah.asets',
      'semester',
      'nilais.mapelMapping.masterMapel.kelompok', 'nilais.mapelMapping.kelompok',
    ])
      ->where('sekolah_id', $sekolah->id)
      ->findOrFail($skhuId);

    $template = SkhuTemplate::aktifUntuk($jenjang, $semester->id);

    if (!$template) {
      return response()->json([
        'message' => 'File template SKHU tidak ditemukan. Hubungi administrator.'
      ], 404);
    }

    $skhu->update(['generated_at' => now()]);

    $html = view($template->view_name, ['skhu' => $skhu])->render();

    $pdf = Pdf::loadHTML($html)
      ->setPaper('a4', 'portrait')
      ->setOptions([
        'isHtml5ParserEnabled' => true,
        'isRemoteEnabled'      => false,
        'defaultFont'          => 'serif',
        'dpi'                  => 150,
      ]);

    $namaSiswa = str_replace(' ', '_', strtoupper($skhu->siswa->nama_siswa));
    $namaFile  = "SKHU_{$namaSiswa}_{$skhu->tahun_pelajaran_clean}.pdf";

    return $pdf->download($namaFile);
  }

  // ── Preview PDF per siswa (stream, dengan QR code) ───────────────────
  public function previewPdf(int $skhuId)
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::findOrFail($user->ptk->sekolah_id);
    $semester = Semester::findOrFail(session('semester_id'));
    $jenjang  = strtoupper($sekolah->jenjang);

    $skhu = Skhu::with([
      'siswa.registrasiTerbaru',
      'sekolah.detailPerSemester.regency',
      'sekolah.asets',
      'semester',
      'nilais.mapelMapping.masterMapel.kelompok', 'nilais.mapelMapping.kelompok',
    ])
      ->where('sekolah_id', $sekolah->id)
      ->findOrFail($skhuId);

    $template = SkhuTemplate::aktifUntuk($jenjang, $semester->id);

    if (!$template) {
      return response()->json([
        'message' => 'File template SKHU tidak ditemukan.'
      ], 404);
    }

    // Generate QR Code
    $urlCekSkhu = route('api.skhu', ['nisn' => $skhu->siswa->nisn]);

    $options = new QROptions([
      'version'          => 5,
      'outputType'       => 'svg',
      'eccLevel'         => 1,
      'scale'            => 10,
      'imageBase64'      => true,
      'drawLightModules' => true,
      'svgConnectPaths'  => true,
    ]);

    $qrCodeBase64 = (new QRCode($options))->render($urlCekSkhu);

    $html = view($template->view_name, [
      'skhu'   => $skhu,
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

    $namaSiswa = str_replace(' ', '_', strtoupper($skhu->siswa->nama_siswa));
    $namaFile  = "PREVIEW_SKHU_{$namaSiswa}.pdf";

    return $pdf->stream($namaFile, ['attachment' => false]);
  }

  // ── Download bulk semua siswa kelas akhir (ZIP) ───────────────────────
  public function generateBulk()
  {
    $user     = Auth::user();
    $sekolah  = Sekolah::with(['detailTerbaru', 'asets'])
      ->findOrFail($user->ptk->sekolah_id);
    $semester = Semester::findOrFail(session('semester_id'));
    $jenjang  = strtoupper($sekolah->jenjang);

    $skhus = Skhu::with([
      'siswa.registrasiTerbaru',
      'sekolah.detailTerbaru',
      'sekolah.asets',
      'semester',
      'nilais.mapelMapping.masterMapel.kelompok', 'nilais.mapelMapping.kelompok',
    ])
      ->where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id)
      ->get();

    if ($skhus->isEmpty()) {
      return response()->json([
        'message' => 'Belum ada data SKHU yang bisa didownload.'
      ], 404);
    }

    $template = SkhuTemplate::aktifUntuk($jenjang, $semester->id);

    if (!$template) {
      return response()->json([
        'message' => 'File template SKHU tidak ditemukan. Hubungi administrator.'
      ], 404);
    }

    $zipNama = "SKHU_{$jenjang}_{$semester->tahun_pelajaran_clean}_" . date('Ymd') . '.zip';
    $zipPath = storage_path("app/temp/{$zipNama}");

    if (!file_exists(storage_path('app/temp'))) {
      mkdir(storage_path('app/temp'), 0755, true);
    }

    $zip = new \ZipArchive();
    $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    foreach ($skhus as $skhu) {
      $skhu->update(['generated_at' => now()]);

      $html = view($template->view_name, ['skhu' => $skhu])->render();

      $pdf = Pdf::loadHTML($html)
        ->setPaper('a4', 'portrait')
        ->setOptions([
          'isHtml5ParserEnabled' => true,
          'isRemoteEnabled'      => false,
          'defaultFont'          => 'serif',
          'dpi'                  => 150,
        ]);

      $namaSiswa = str_replace(' ', '_', strtoupper($skhu->siswa->nama_siswa));
      $zip->addFromString("SKHU_{$namaSiswa}.pdf", $pdf->output());
    }

    $zip->close();

    return response()->download($zipPath, $zipNama)->deleteFileAfterSend(true);
  }

  // =========================================================================
  // GENERATE TEMPLATE EXCEL
  // =========================================================================
  public function downloadTemplate(Request $request)
  {
    $user = Auth::user();

    if (!$user->hasRole('Operator Sekolah')) {
      abort(403);
    }

    $semester = Semester::where('id', session('semester_id'))->first()
      ?? Semester::where('is_active', 1)->first();

    $sekolahId = $user->ptk->sekolah_id;

    if (!$sekolahId) {
      return response()->json(['message' => 'Sekolah tidak ditemukan.'], 422);
    }

    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $jenjang = strtoupper($sekolah->jenjang);

    $tingkatAkhir = $this->tingkatAkhir[$jenjang] ?? 12;

    $mapels = $this->getMapels($jenjang, $tingkatAkhir, $semester->id, $sekolah->id);

    if ($mapels->isEmpty()) {
      return response()->json([
        'message' => "Belum ada mapel untuk jenjang {$jenjang} semester {$semester->kode_semester}.",
      ], 422);
    }

    $siswaList = RombelAnggota::with(['siswa', 'siswa.skhu', 'rombel'])
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

    // ── Buat Excel ─────────────────────────────────────────────────────────
    $spreadsheet = new Spreadsheet();
    $sheet       = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Nilai SKHU');

    $colorHeader    = 'FF1A56A4'; // Biru Tua untuk info siswa
    $colorNomor     = 'FF0E7C4A'; // Hijau untuk Nomor Surat/Ujian
    $colorGroup     = 'FF2C3E50'; // Abu-abu gelap untuk Kelompok Mapel
    $colorMapelHead = 'FFD6E4F7'; // Biru muda untuk Nama Mapel
    $colorDesc      = 'FFEAF2FB'; // Biru sangat muda untuk keterangan
    $colorFixed     = 'FFF0F4F8'; // Abu-abu sangat muda untuk kolom terkunci
    $colorNomorCell = 'FFFFFDE7'; // Kuning muda untuk cell input nomor

    $kolomTetap  = ['No', 'Nama Siswa', 'NISN', 'Rombel'];
    $kolomNomor  = ['nomor_surat||Nomor Surat', 'nomor_ujian||Nomor Peserta Ujian'];
    $jumlahFixed = count($kolomTetap);
    $jumlahNomor = count($kolomNomor);
    $mapelStartCol = $jumlahFixed + $jumlahNomor + 1;

    // ── BARIS 1: TECHNICAL KEYS (Akan disembunyikan) ──────────────────────
    $col = 1;
    foreach ($kolomTetap as $label) {
      $sheet->setCellValue([$col, 1], $label);
      $col++;
    }
    foreach ($kolomNomor as $key) {
      $sheet->setCellValue([$col, 1], $key);
      $col++;
    }
    foreach ($mapels as $mapel) {
      $namaMapel = $mapel->masterMapel->nama;
      $sheet->setCellValue([$col, 1], "mapel_mapping_{$mapel->id}||{$namaMapel}");
      $col++;
    }
    // Sembunyikan baris 1 dari pandangan user!
    $sheet->getRowDimension(1)->setVisible(false);

    // ── BARIS 2 & 3: HEADER VISUAL ──────────────────────────────────────────
    $col = 1;

    // Header Info Siswa (Merge Baris 2 & 3)
    foreach ($kolomTetap as $label) {
      $sheet->mergeCells([$col, 2, $col, 3]);
      $sheet->setCellValue([$col, 2], $label);
      $sheet->getStyle([$col, 2, $col, 3])->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorHeader]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFAAAAAA']]],
      ]);
      $col++;
    }

    // Header Nomor Surat & Ujian (Merge Baris 2 & 3)
    foreach ($kolomNomor as $key) {
      $label = explode('||', $key)[1];
      $sheet->mergeCells([$col, 2, $col, 3]);
      $sheet->setCellValue([$col, 2], $label);
      $sheet->getStyle([$col, 2, $col, 3])->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorNomor]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFAAAAAA']]],
      ]);
      $col++;
    }

    // Header Mapel (Dikelompokkan)
    $mapelsGrouped = $mapels->groupBy(fn($m) => $m->kelompokTampil()?->nama ?? 'Lainnya');
    $currentCol = $mapelStartCol;

    foreach ($mapelsGrouped as $kelompokNama => $items) {
      $groupStartCol = $currentCol;

      foreach ($items as $mapel) {
        $namaMapel = $mapel->masterMapel->nama;
        // Baris 3: Nama Mapel Visual
        $sheet->setCellValue([$currentCol, 3], $namaMapel);
        $sheet->getStyle([$currentCol, 3])->applyFromArray([
          'font'      => ['bold' => true, 'color' => ['rgb' => 'FF1A56A4'], 'size' => 9],
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorMapelHead]],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
          'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFAAAAAA']]],
        ]);

        // Tambahkan komentar di Excel
        $comment = $sheet->getComment(Coordinate::stringFromColumnIndex($currentCol) . '3');
        $jenis = ucfirst($mapel->jenis ?? 'Pokok');
        $comment->getText()->createTextRun("Mapel: {$namaMapel}\nKelompok: {$kelompokNama}\nJenis: {$jenis}");

        $sheet->getColumnDimensionByColumn($currentCol)->setWidth(14);
        $currentCol++;
      }

      $groupEndCol = $currentCol - 1;

      // Baris 2: Kelompok Mapel (Di-merge)
      if ($groupStartCol == $groupEndCol) {
        $sheet->setCellValue([$groupStartCol, 2], $kelompokNama);
      } else {
        $sheet->mergeCells([$groupStartCol, 2, $groupEndCol, 2]);
        $sheet->setCellValue([$groupStartCol, 2], $kelompokNama);
      }

      $sheet->getStyle([$groupStartCol, 2, $groupEndCol, 2])->applyFromArray([
        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFFFF'], 'size' => 10],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorGroup]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFAAAAAA']]],
      ]);
    }

    // ── BARIS 4: LABEL DESKRIPTIF ───────────────────────────────────────────
    $col = 1;
    foreach ($kolomTetap as $_) {
      $sheet->setCellValue([$col, 4], '(tidak diubah)');
      $sheet->getStyle([$col, 4])->applyFromArray([
        'font'      => ['italic' => true, 'color' => ['rgb' => 'FF888888'], 'size' => 8],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorFixed]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFCCCCCC']]],
      ]);
      $col++;
    }
    foreach ($kolomNomor as $_) {
      $sheet->setCellValue([$col, 4], '(isi nomor)');
      $sheet->getStyle([$col, 4])->applyFromArray([
        'font'      => ['italic' => true, 'color' => ['rgb' => 'FF0E7C4A'], 'size' => 8],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFE8F5E9']],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFCCCCCC']]],
      ]);
      $col++;
    }
    for ($i = $mapelStartCol; $i < $currentCol; $i++) {
      $sheet->setCellValue([$i, 4], '(0 - 100)');
      $sheet->getStyle([$i, 4])->applyFromArray([
        'font'      => ['italic' => true, 'color' => ['rgb' => 'FF1A3A6A'], 'size' => 8],
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorDesc]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFCCCCCC']]],
      ]);
    }

    // ── BARIS 5++: DATA SISWA ───────────────────────────────────────────────
    $dataSiswa = [];
    $no        = 1;

    foreach ($siswaList as $anggota) {
      $siswa        = $anggota->siswa;
      $skhuExisting = $siswa->skhu->first();

      $rowData = [
        $no++,
        $siswa->nama_siswa,
        $siswa->nisn,
        $anggota->rombel->nama_rombel ?? '-',
        $skhuExisting?->nomor_surat ?? '',
        $skhuExisting?->nomor_ujian ?? '',
      ];

      foreach ($mapels as $_) {
        $rowData[] = '';
      }

      $dataSiswa[] = $rowData;
    }

    if (!empty($dataSiswa)) {
      $sheet->fromArray($dataSiswa, null, 'A5');
    }

    $lastDataRow = 4 + count($siswaList);

    if ($lastDataRow >= 5) {
      $colNomorSurat = Coordinate::stringFromColumnIndex($jumlahFixed + 1);
      $colNomorUjian = Coordinate::stringFromColumnIndex($jumlahFixed + 2);
      $colMapelAwal  = Coordinate::stringFromColumnIndex($mapelStartCol);
      $colMapelAkhir = Coordinate::stringFromColumnIndex($jumlahFixed + $jumlahNomor + count($mapels));

      $sheet->getStyle("A5:D{$lastDataRow}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorFixed]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFDDDDDD']]],
        'font'      => ['size' => 9],
      ]);
      $sheet->getStyle("A5:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
      $sheet->getStyle("B5:D{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

      $sheet->getStyle("{$colNomorSurat}5:{$colNomorUjian}{$lastDataRow}")->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => $colorNomorCell]],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFBBCC99']]],
        'font'      => ['size' => 9],
      ]);

      if ($mapels->count() > 0) {
        $sheet->getStyle("{$colMapelAwal}5:{$colMapelAkhir}{$lastDataRow}")->applyFromArray([
          'fill'      => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FFFFFFFF']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
          'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFCCDDEE']]],
          'font'      => ['size' => 9],
        ]);

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_DECIMAL);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Nilai Tidak Valid');
        $validation->setError('Nilai harus antara 0 dan 100.');
        $validation->setFormula1(0);
        $validation->setFormula2(100);

        $sheet->setDataValidation("{$colMapelAwal}5:{$colMapelAkhir}{$lastDataRow}", $validation);
      }

      $sheet->getStyle("{$colNomorSurat}5:{$colMapelAkhir}{$lastDataRow}")
        ->getProtection()->setLocked(Protection::PROTECTION_UNPROTECTED);

      for ($r = 5; $r <= $lastDataRow; $r++) {
        $sheet->getRowDimension($r)->setRowHeight(18);
      }
    }

    $sheet->getColumnDimension('A')->setWidth(5);
    $sheet->getColumnDimension('B')->setWidth(30);
    $sheet->getColumnDimension('C')->setWidth(14);
    $sheet->getColumnDimension('D')->setWidth(14);
    $sheet->getColumnDimension('E')->setWidth(18);
    $sheet->getColumnDimension('F')->setWidth(18);

    $sheet->getRowDimension(2)->setRowHeight(25);
    $sheet->getRowDimension(3)->setRowHeight(35);
    $sheet->getRowDimension(4)->setRowHeight(15);

    $freezeCol = Coordinate::stringFromColumnIndex($mapelStartCol);
    $sheet->freezePane("{$freezeCol}5");

    $prot = $sheet->getProtection();
    $prot->setSheet(true);
    $prot->setPassword('021');
    $prot->setSelectLockedCells(true);
    $prot->setSelectUnlockedCells(false);

    $spreadsheet->getProperties()
      ->setTitle("Template Nilai SKHU — {$jenjang} {$semester->tahun_pelajaran}")
      ->setDescription("Generate: " . now()->toDateTimeString());

    $namaFile = "template-skhu-{$jenjang}-" . str_replace('/', '-', $semester->tahun_pelajaran) . ".xlsx";
    $writer   = new XlsxWriter($spreadsheet);

    return response()->streamDownload(function () use ($writer) {
      $writer->save('php://output');
    }, $namaFile, [
      'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Cache-Control' => 'max-age=0',
    ]);
  }

  // =========================================================================
  // IMPORT NILAI SKHU DARI EXCEL
  // =========================================================================
  public function importNilai(Request $request)
  {
    $user = Auth::user();

    if (!$user->hasRole('Operator Sekolah')) {
      return response()->json(['message' => 'Akses ditolak.'], 403);
    }

    $request->validate([
      'tanggal_mulai_ujian'   => 'nullable|date',
      'tanggal_selesai_ujian' => 'nullable|date',
      'tanggal_terbit'        => 'nullable|date',
      'file_excel'            => 'required|file|mimes:xlsx,xls|max:10240',
    ]);

    $semester = Semester::where('id', session('semester_id'))->first()
      ?? Semester::where('is_active', 1)->first();

    if (!$semester) {
      return response()->json(['message' => 'Semester aktif tidak ditemukan.'], 422);
    }

    $sekolahId = $user->ptk->sekolah_id ?? null;

    if (!$sekolahId) {
      return response()->json(['message' => 'Data sekolah operator tidak ditemukan.'], 422);
    }

    $sekolah    = \App\Models\Sekolah::findOrFail($sekolahId);
    $dataHeader = [
      'tanggal_mulai_ujian'   => $request->tanggal_mulai_ujian,
      'tanggal_selesai_ujian' => $request->tanggal_selesai_ujian,
      'tanggal_terbit'        => $request->tanggal_terbit,
    ];

    try {
      $import = new SkhuImport(
        sekolahId: $sekolah->id,
        semesterId: $semester->id,
        jenjang: $sekolah->jenjang,
        tahunPelajaran: $semester->tahun_pelajaran,
        dataHeader: $dataHeader,
      );

      Excel::import($import, $request->file('file_excel'));

      if ($import->hasErrors()) {
        return response()->json([
          'status'      => 'error',
          'message'     => 'Import dibatalkan. Terdapat ' . count($import->getErrors()) . ' kesalahan.',
          'errors'      => $import->getErrors(),
          'total_error' => count($import->getErrors()),
        ], 422);
      }

      $berhasil   = $import->getBerhasil();
      $diperbarui = $import->getDiperbarui();
      $pesan      = [];
      if ($berhasil > 0)   $pesan[] = "{$berhasil} siswa baru diimport";
      if ($diperbarui > 0) $pesan[] = "{$diperbarui} data siswa diperbarui";

      return response()->json([
        'status'     => 'success',
        'message'    => implode(', ', $pesan) . '.',
        'berhasil'   => $berhasil,
        'diperbarui' => $diperbarui,
      ]);
    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'File Excel tidak valid.',
        'errors'  => [['baris' => '-', 'kolom' => 'File', 'nilai' => '-', 'pesan' => $e->getMessage()]],
      ], 422);
    } catch (\Exception $e) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Gagal memproses file: ' . $e->getMessage(),
      ], 500);
    }
  }
}
