<?php

namespace App\Http\Controllers;

use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\SekolahLogo;
use App\Models\Semester;
use App\Models\Siswa;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Kartu Peserta Ujian: generator kartu peserta untuk penilaian /
 * ujian sekolah. Kepala kartu = judul, nama sekolah, dan tahun
 * pelajaran, diapit logo kiri/kanan pilihan pengguna (pemda /
 * sekolah / Tut Wuri Handayani). Field kartu tetap — nomor,
 * rombel, TTL, NIS/NISN — dengan urutan yang bisa disusun
 * pengguna. Pengaturan diisi per cetakan lewat formulir, tidak
 * disimpan. Pola nomor memakai token {no}, {no:3}, {t}, {t:2},
 * {tahun}; penomoran bisa berurutan per tingkat atau menyeluruh.
 */
class KartuUjianController extends Controller
{
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
   * Sekolah konteks pengguna — pola yang sama dengan SKHU/SKL dan
   * Kartu Pelajar. Null hanya untuk Admin yang belum memilih di
   * antara beberapa sekolah; peran lain tanpa tautan sekolah 403.
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

    $rombels = $sekolahId
      ? Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', session('semester_id'))
        ->orderBy('nama_rombel')
        ->get()
      : collect();

    $daftarSekolah = Auth::user()->hasRole('Admin')
      ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
      : collect();

    // Nilai bawaan formulir dari data sekolah konteks: penanda
    // tangan = kepala sekolah, NIP-nya, tempat titimangsa =
    // kecamatan sekolah, tahun pelajaran dari semester sesi.
    // Semua masih bisa diubah di formulir.
    $detail = $sekolahId ? Sekolah::find($sekolahId)?->detailTerbaru : null;
    $bawaan = [
      'penanda_tangan'    => $detail->kepala_sekolah ?? '',
      'nip_penanda'       => $detail->nip ?? '',
      'titimangsa_tempat' => '',
      'titimangsa_tanggal' => now()->format('Y-m-d'),
      'tahun_pelajaran'   => Semester::find(session('semester_id'))?->tahun_pelajaran ?? '',
    ];

    if ($detail) {
      $detail->loadMissing('district');
      if ($detail->district?->name) {
        $bawaan['titimangsa_tempat'] = ucwords(strtolower($detail->district->name));
      }
    }

    return view('content.kartu-ujian.index', compact('rombels', 'sekolahId', 'daftarSekolah', 'bawaan'));
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
    $data = $request->validate([
      'siswa_ids'          => 'required|array|min:1',
      'siswa_ids.*'        => 'exists:tabel_siswa,id',
      'judul'              => 'required|string|max:45',
      'tahun_pelajaran'    => 'required|string|max:20',
      'pola_nomor'         => 'required|string|max:60',
      'penomoran'          => 'required|in:per_tingkat,semua',
      'logo_kiri'          => 'required|in:pemda,sekolah,twh,kosong',
      'logo_kanan'         => 'required|in:pemda,sekolah,twh,kosong',
      'urutan_nomor'       => 'nullable|integer|between:1,4',
      'urutan_rombel'      => 'nullable|integer|between:1,4',
      'urutan_ttl'         => 'nullable|integer|between:1,4',
      'urutan_nis_nisn'    => 'nullable|integer|between:1,4',
      'titimangsa_tempat'  => 'required|string|max:100',
      'titimangsa_tanggal' => 'required|date',
      'penanda_tangan'     => 'required|string|max:100',
      'nip_penanda'        => 'nullable|string|max:30',
    ]);

    if (!preg_match('/\{no(?:[:;]\d+)?\}/', $data['pola_nomor'])) {
      return back()->withInput()
        ->with('error', 'Pola nomor peserta harus mengandung token nomor, contoh {no:3}.');
    }

    $sekolahId = $this->sekolahKonteksIdNullable();

    if (!$sekolahId) {
      return back()->withInput()
        ->with('error', 'Pilih sekolah dulu lewat pemilih konteks sekolah di halaman Kartu Ujian.');
    }

    $semesterId = (int) session('semester_id');

    $siswaList = Siswa::with([
      'rombels' => fn($q) => $q->orderByDesc('tabel_rombel_anggota.id'),
      'registrasiTerbaru',
    ])
      ->whereIn('id', $data['siswa_ids'])
      ->whereHas('registrasiTerbaru', fn($q) => $q->where('sekolah_id', $sekolahId))
      ->get();

    if ($siswaList->isEmpty()) {
      return back()->withInput()
        ->with('error', 'Tidak ada siswa terpilih yang termasuk sekolah konteks Anda.');
    }

    // Kelas acuan = rombel siswa pada semester sesi (jatuh ke rombel
    // terakhirnya bila semester ini belum punya rombel).
    foreach ($siswaList as $siswa) {
      $rombelSesi = $siswa->rombels->firstWhere('semester_id', $semesterId)
        ?? $siswa->rombels->first();
      $siswa->kelas_label = $rombelSesi?->nama_rombel ?? '-';
      $siswa->tingkat_acuan = (int) ($rombelSesi?->tingkat ?? 0);
      $siswa->nis_label = $siswa->registrasiTerbaru?->nis ?? '-';
      $siswa->nis_nisn_label = $siswa->nis_label . ' / ' . ($siswa->nisn ?? '-');
      $siswa->ttl_label = trim(
        ($siswa->tempat_lahir ?? '-') . ', ' .
          ($siswa->tgl_lahir
            ? $siswa->tgl_lahir->day . ' ' . $this->bulanIndo[$siswa->tgl_lahir->month] . ' ' . $siswa->tgl_lahir->year
            : '-')
      );
      $siswa->jk_label = $siswa->jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
    }

    // Urutan cetak: tingkat, lalu nama rombel, lalu nama siswa.
    $siswaList = $siswaList
      ->sortBy(fn($s) => [$s->tingkat_acuan, $s->kelas_label, $s->nama_siswa])
      ->values();

    // Penomoran peserta sesuai pola.
    $tahun = (int) date('Y', strtotime($data['titimangsa_tanggal']));
    $hitungSemua = 0;
    $hitungPerTingkat = [];
    foreach ($siswaList as $siswa) {
      if ($data['penomoran'] === 'per_tingkat') {
        $hitungPerTingkat[$siswa->tingkat_acuan] = ($hitungPerTingkat[$siswa->tingkat_acuan] ?? 0) + 1;
        $nomor = $hitungPerTingkat[$siswa->tingkat_acuan];
      } else {
        $nomor = ++$hitungSemua;
      }
      $siswa->nomor_peserta = $this->formatNomor($data['pola_nomor'], $nomor, $siswa->tingkat_acuan, $tahun);
      $siswa->nilai_field = [
        'nomor'    => $siswa->nomor_peserta,
        'rombel'   => $siswa->kelas_label,
        'ttl'      => $siswa->ttl_label,
        'nis_nisn' => $siswa->nis_nisn_label,
      ];
    }

    $sekolah = Sekolah::find($sekolahId);
    $detail = $sekolah?->detailTerbaru;

    $sekolahGlobal = [
      'nama_sekolah' => $detail->nama_sekolah ?? '-',
      'npsn'         => $sekolah?->npsn ?? '-',
    ];

    $logos = SekolahLogo::where('sekolah_id', $sekolahId)
      ->whereIn('tipe', [
        SekolahLogo::TIPE_LOGO_KABUPATEN,
        SekolahLogo::TIPE_LOGO_SEKOLAH,
        SekolahLogo::TIPE_TTD_KS,
        SekolahLogo::TIPE_STEMPEL,
      ])
      ->get()->keyBy('tipe');

    $ambilPath = function (string $tipe) use ($logos) {
      $logo = $logos->get($tipe);
      return $logo && file_exists($logo->absolute_path) ? $logo->absolute_path : null;
    };

    // Logo pengapit kepala kartu: pemda / sekolah dari tabel logo,
    // twh (Tut Wuri Handayani) dari berkas aset yang ditaruh manual
    // di public/assets/img/logo-twh.png.
    $ambilLogo = function (string $pilihan) use ($ambilPath) {
      return match ($pilihan) {
        'pemda'   => $ambilPath(SekolahLogo::TIPE_LOGO_KABUPATEN),
        'sekolah' => $ambilPath(SekolahLogo::TIPE_LOGO_SEKOLAH),
        'twh'     => file_exists(public_path('assets/img/logo-twh.png'))
          ? public_path('assets/img/logo-twh.png')
          : null,
        default   => null,
      };
    };

    $pengaturan = [
      'judul'           => $data['judul'],
      'tahun_pelajaran' => $data['tahun_pelajaran'],
      'pakai_foto'      => $request->boolean('pakai_foto'),
      'pakai_ttd'       => $request->boolean('pakai_ttd'),
      'pakai_stempel'   => $request->boolean('pakai_stempel'),
      'titimangsa'      => $data['titimangsa_tempat'] . ', ' . $this->labelTanggal($data['titimangsa_tanggal']),
      'penanda_tangan'  => $data['penanda_tangan'],
      'nip_penanda'     => $data['nip_penanda'] ?? '',
      'logoKiriPath'    => $ambilLogo($data['logo_kiri']),
      'logoKananPath'   => $ambilLogo($data['logo_kanan']),
      'ttdPath'         => $ambilPath(SekolahLogo::TIPE_TTD_KS),
      'stempelPath'     => $ambilPath(SekolahLogo::TIPE_STEMPEL),
      'urutan_field'    => $this->urutanField($data),
      'label_field'     => [
        'nomor'    => 'Nomor',
        'rombel'   => 'Rombel',
        'ttl'      => 'TTL',
        'nis_nisn' => 'NIS/NISN',
      ],
    ];

    $pdf = Pdf::setPaper('a4', 'portrait');
    $pdf->setOption(['isRemoteEnabled' => true, 'defaultFont' => 'sans-serif']);
    $pdf->loadView('content.kartu-ujian.cetak', compact('siswaList', 'sekolahGlobal', 'pengaturan'));

    return $pdf->stream('kartu-peserta-ujian.pdf');
  }

  /**
   * Pola → nomor peserta jadi. Token: {no}, {no:3} / {no;3} (nomor
   * urut, diberi nol di depan sampai lebar digit), {t}, {t:2} / {t;2}
   * (tingkat), dan {tahun}. Contoh: "PS-{t:1}-{no:3}" → "PS-6-007".
   * {t:1} berarti tingkat ditulis apa adanya (lebar 1 tidak menambah
   * nol untuk tingkat satu digit).
   */
  private function formatNomor(string $pola, int $nomor, int $tingkat, int $tahun): string
  {
    $hasil = preg_replace_callback(
      '/\{no(?:[:;](\d+))?\}/',
      fn($m) => isset($m[1]) && $m[1] !== ''
        ? str_pad((string) $nomor, (int) $m[1], '0', STR_PAD_LEFT)
        : (string) $nomor,
      trim($pola)
    );

    $hasil = preg_replace_callback(
      '/\{t(?:[:;](\d+))?\}/',
      fn($m) => isset($m[1]) && $m[1] !== ''
        ? str_pad((string) $tingkat, (int) $m[1], '0', STR_PAD_LEFT)
        : (string) $tingkat,
      $hasil
    );

    return str_replace('{tahun}', (string) $tahun, $hasil);
  }

  /**
   * Urutan field pada kartu dari pilihan pengguna. Bawaan: nomor,
   * rombel, ttl, nis/nisn. Angka urutan yang sama diselesaikan
   * dengan urutan bawaan, jadi tidak ada field yang hilang.
   */
  private function urutanField(array $data): array
  {
    $bawaan = ['nomor' => 1, 'rombel' => 2, 'ttl' => 3, 'nis_nisn' => 4];
    $kunci = array_keys($bawaan);

    usort($kunci, function ($a, $b) use ($data, $bawaan) {
      $ua = (int) ($data['urutan_' . $a] ?? $bawaan[$a]);
      $ub = (int) ($data['urutan_' . $b] ?? $bawaan[$b]);
      return [$ua, $bawaan[$a]] <=> [$ub, $bawaan[$b]];
    });

    return $kunci;
  }

  /** "2026-11-10" → "10 November 2026". */
  private function labelTanggal(string $tanggal): string
  {
    $waktu = strtotime($tanggal);
    return date('j', $waktu) . ' ' . $this->bulanIndo[(int) date('n', $waktu)] . ' ' . date('Y', $waktu);
  }
}
