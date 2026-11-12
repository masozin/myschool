<?php

namespace App\Http\Controllers;

use App\Models\JadwalPelajaran;
use App\Models\JadwalPengaturan;
use App\Models\MapelMapping;
use App\Models\Pengampu;
use App\Models\Ptk;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\Semester;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Protection;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

/**
 * Jadwal Pelajaran (fase 1: fondasi & penyusunan).
 *
 * Tiga lapis data: kerangka jam per sekolah+semester
 * (tabel_jadwal_pengaturan), penetapan guru pengampu per
 * rombel+mapel (tabel_pengampu — tabel_mapel_mapping memang
 * tidak menyimpan guru), dan isi kotak hari x jam
 * (tabel_jadwal). Keputusan desain (Mark, 2026-11-11):
 * pengampu lapisan tersendiri; kerangka per hari; hari sekolah
 * mengikuti libur mingguan Profil Sekolah; yang menyusun hanya
 * Operator Sekolah (peran lain melihat); bentrok guru DIBLOKIR
 * saat menyimpan; target JP/minggu ditunda (kolom target_jam
 * disiapkan, belum dipakai).
 */
class JadwalPelajaranController extends Controller
{
  private const HARI = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
  ];

  /**
   * Panel guru di halaman Jadwal Pelajaran: apa yang sedang
   * berlangsung SAAT INI (berdasarkan jam sekarang) dan daftar
   * jadwal mengajar guru hari ini, dari kerangka tiap rombel.
   * Hari mengikuti nomor hari ISO (1=Senin .. 7=Minggu).
   */
  private function panelGuruHariIni(Sekolah $sekolah, int $semesterId, int $ptkId, ?Rombel $rombelWali): array
  {
    $sekarang = now();
    $hariIni = (int) $sekarang->dayOfWeekIso; // 1=Senin .. 7=Minggu
    $jamMenit = $sekarang->hour * 60 + $sekarang->minute;
    $keMenit = function (string $w): int {
      [$j, $m] = array_map('intval', explode('.', str_replace(':', '.', $w)));
      return $j * 60 + $m;
    };
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    // Garis waktu hari ini per rombel (model rombel bisa berbeda).
    $waktuRombel = [];
    $waktuUntuk = function (Rombel $r) use (&$waktuRombel, $sekolah, $semesterId, $hariIni) {
      if (!array_key_exists($r->id, $waktuRombel)) {
        $frame = $this->kerangka($sekolah, $semesterId, $r)['hari'][$hariIni] ?? null;
        $waktuRombel[$r->id] = $frame ? $this->garisWaktu($frame) : null;
      }
      return $waktuRombel[$r->id];
    };

    // Semua kotak mengajar guru ini hari ini (lintas rombel).
    $barisSaya = JadwalPelajaran::with(['rombel', 'pengampu.mapping.masterMapel', 'pembina'])
      ->where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semesterId)
      ->where('hari', $hariIni)
      ->where(function ($q) use ($ptkId) {
        $q->whereHas('pengampu', fn($qq) => $qq->where('ptk_id', $ptkId))
          ->orWhere('pembina_id', $ptkId);
      })
      ->get();

    $daftar = [];
    foreach ($barisSaya as $b) {
      if (!$b->rombel) {
        continue;
      }
      $rentang = $waktuUntuk($b->rombel)['pelajaran'][$b->jam_ke] ?? null;
      if (!$rentang) {
        continue;
      }
      $daftar[] = [
        'mulai' => $rentang[0],
        'selesai' => $rentang[1],
        'mulai_menit' => $keMenit($rentang[0]),
        'selesai_menit' => $keMenit($rentang[1]),
        'rombel' => $b->rombel->nama_rombel,
        'keterangan' => $b->pengampu
          ? ($b->pengampu->mapping?->masterMapel?->nama ?? 'Pelajaran')
          : ('Kegiatan: ' . ($b->nama_kegiatan ?? '-')),
        'jam_ke' => $b->jam_ke,
      ];
    }
    usort($daftar, fn($a, $b) => $a['mulai_menit'] <=> $b['mulai_menit']);

    $sedang = null;
    $berikutnya = null;
    foreach ($daftar as $d) {
      if ($d['mulai_menit'] <= $jamMenit && $jamMenit < $d['selesai_menit']) {
        $sedang = $d;
      }
      if ($berikutnya === null && $d['mulai_menit'] > $jamMenit) {
        $berikutnya = $d;
      }
    }

    // Bila guru ini wali kelas: apa yang sedang berlangsung di kelasnya.
    $kelasSekarang = null;
    if ($rombelWali && ($w = $waktuUntuk($rombelWali))) {
      $selWali = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
        ->where('rombel_id', $rombelWali->id)
        ->where('semester_id', $semesterId)
        ->where('hari', $hariIni)
        ->get()
        ->keyBy('jam_ke');
      foreach ($w['pelajaran'] as $jamKe => $rentang) {
        if ($keMenit($rentang[0]) <= $jamMenit && $jamMenit < $keMenit($rentang[1])) {
          $sel = $selWali->get($jamKe);
          $kelasSekarang = [
            'rombel' => $rombelWali->nama_rombel,
            'mulai' => $rentang[0],
            'selesai' => $rentang[1],
            'keterangan' => $sel?->pengampu
              ? ($sel->pengampu->mapping?->masterMapel?->nama ?? 'Pelajaran')
              : ($sel?->nama_kegiatan ?? 'Belum diisi'),
            'guru' => $sel?->pengampu?->ptk?->nama_ptk ?? $sel?->pembina?->nama_ptk,
          ];
          break;
        }
      }
      if (!$kelasSekarang) {
        foreach ($w['lainnya'] as $evs) {
          foreach ($evs as $ev) {
            if ($keMenit($ev['waktu'][0]) <= $jamMenit && $jamMenit < $keMenit($ev['waktu'][1])) {
              $kelasSekarang = [
                'rombel' => $rombelWali->nama_rombel,
                'mulai' => $ev['waktu'][0],
                'selesai' => $ev['waktu'][1],
                'keterangan' => $ev['nama'],
                'guru' => null,
              ];
              break 2;
            }
          }
        }
      }
    }

    return [
      'hari_nama' => self::HARI[$hariIni] ?? 'Minggu',
      'tanggal' => $sekarang->format('d') . ' ' . $bulan[(int) $sekarang->format('n')] . ' ' . $sekarang->format('Y'),
      'jam' => $sekarang->format('H.i'),
      'daftar' => $daftar,
      'sedang' => $sedang,
      'berikutnya' => $berikutnya,
      'kelas_sekarang' => $kelasSekarang,
    ];
  }

  // ── Ringkasan per rombel ──────────────────────────────────────
  public function index()
  {
    $user = Auth::user();
    $sekolahId = $this->sekolahKonteksIdNullable();
    $daftarSekolah = $user->hasRole('Admin')
      ? Sekolah::with('detailTerbaru')->orderBy('id')->get()
      : collect();
    $bolehSusun = $user->hasRole('Operator Sekolah');
    $semester = Semester::find($this->semesterId());

    if (!$sekolahId) {
      return view('content.jadwal-pelajaran.index', [
        'sekolahId' => null,
        'daftarSekolah' => $daftarSekolah,
        'sekolah' => null,
        'semester' => $semester,
        'rombels' => collect(),
        'pengampuPerRombel' => collect(),
        'jadwalPerRombel' => collect(),
        'modelPerRombel' => [],
        'totalKotak' => 0,
        'pengaturanAda' => false,
        'bolehSusun' => $bolehSusun,
        'daftarModel' => collect(),
        'panelGuru' => null,
      ]);
    }

    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $kerangka = $this->kerangka($sekolah, $semesterId);
    $totalKotak = array_sum(array_map(
      fn($f) => (int) ($f['jumlah_jam'] ?? 0),
      $kerangka['hari']
    ));

    $rombels = Rombel::with(['ptk', 'jadwalPengaturan'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get();

    $daftarModel = JadwalPengaturan::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->orderBy('id')
      ->get();

    // Guru murni hanya melihat rombel yang diwalikan / diajarnya.
    if (
      $user->hasRole('Guru')
      && !$user->hasAnyRole(['Admin', 'Operator Sekolah', 'Kepala Sekolah'])
    ) {
      $ptkId = $user->ptk?->id;
      $rombelDiajar = Pengampu::where('semester_id', $semesterId)
        ->where('ptk_id', $ptkId)
        ->pluck('rombel_id');
      $rombels = $rombels
        ->filter(fn($r) => $r->ptk_id === $ptkId || $rombelDiajar->contains($r->id))
        ->values();
    }

    // Model jadwal + total kotak per rombel menurut tingkatnya.
    $modelPerRombel = [];
    foreach ($rombels as $r) {
      $k = $this->kerangka($sekolah, $semesterId, $r);
      $modelPerRombel[$r->id] = [
        'nama' => $k['model']?->nama ?? 'Pola bawaan',
        'total' => array_sum(array_map(
          fn($f) => (int) ($f['jumlah_jam'] ?? 0),
          $k['hari']
        )),
      ];
    }

    // Panel guru: jadwal saat ini + jadwal mengajar hari ini.
    $panelGuru = null;
    if ($user->hasRole('Guru') && $user->ptk) {
      $rombelWali = $rombels->firstWhere('ptk_id', (int) $user->ptk->id);
      $panelGuru = $this->panelGuruHariIni(
        $sekolah,
        $semesterId,
        (int) $user->ptk->id,
        $rombelWali
      );
    }

    $ids = $rombels->pluck('id');
    $pengampuPerRombel = Pengampu::where('semester_id', $semesterId)
      ->whereIn('rombel_id', $ids)
      ->selectRaw('rombel_id, COUNT(*) AS c')
      ->groupBy('rombel_id')
      ->pluck('c', 'rombel_id');
    $jadwalPerRombel = JadwalPelajaran::where('semester_id', $semesterId)
      ->whereIn('rombel_id', $ids)
      ->selectRaw('rombel_id, COUNT(*) AS c')
      ->groupBy('rombel_id')
      ->pluck('c', 'rombel_id');

    return view('content.jadwal-pelajaran.index', [
      'sekolahId' => $sekolahId,
      'daftarSekolah' => $daftarSekolah,
      'sekolah' => $sekolah,
      'semester' => $semester,
      'rombels' => $rombels,
      'pengampuPerRombel' => $pengampuPerRombel,
      'jadwalPerRombel' => $jadwalPerRombel,
      'modelPerRombel' => $modelPerRombel,
      'totalKotak' => $totalKotak,
      'pengaturanAda' => $kerangka['tersimpan'],
      'bolehSusun' => $bolehSusun,
      'daftarModel' => $daftarModel,
      'panelGuru' => $panelGuru,
    ]);
  }

  // ── Pengaturan kerangka jam (model jadwal) ────────────────────
  public function pengaturan()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $models = $this->daftarModel($sekolahId, $semesterId);

    // Pemakai = rombel yang secara eksplisit memilih model ini.
    $pemakai = Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->whereNotNull('jadwal_pengaturan_id')
      ->selectRaw('jadwal_pengaturan_id, COUNT(*) AS c')
      ->groupBy('jadwal_pengaturan_id')
      ->pluck('c', 'jadwal_pengaturan_id');

    return view('content.jadwal-pelajaran.pengaturan', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($semesterId),
      'models' => $models,
      'pemakai' => $pemakai,
      'namaHari' => self::HARI,
    ]);
  }

  public function tambahModel(Request $request)
  {
    $data = $request->validate([
      'nama' => 'required|string|max:50',
    ]);

    $sekolahId = $this->sekolahKonteksIdNullable();
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();

    $model = JadwalPengaturan::create([
      'sekolah_id' => $sekolahId,
      'semester_id' => $semesterId,
      'nama' => $data['nama'],
      'kerangka' => $this->kerangkaBawaan($sekolah),
    ]);
    $this->memoModel = [];

    return redirect()
      ->route('jadwal-pelajaran.pengaturan.model', $model->id)
      ->with('success', "Model \"{$model->nama}\" dibuat dari pola bawaan — sesuaikan kerangka jamnya di bawah ini.");
  }

  public function pengaturanModel($id)
  {
    $model = $this->cariModel($id);
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($model->sekolah_id);
    $kerangka = $this->kerangkaModel($model, $sekolah);

    return view('content.jadwal-pelajaran.pengaturan-model', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($model->semester_id),
      'model' => $model,
      'kerangka' => $kerangka['hari'],
      'tersimpan' => $kerangka['tersimpan'],
      'namaHari' => self::HARI,
    ]);
  }

  public function hapusModel($id)
  {
    $model = $this->cariModel($id);
    $nama = $model->nama;
    $model->delete();
    $this->memoModel = [];

    return redirect()
      ->route('jadwal-pelajaran.pengaturan')
      ->with('success', "Model \"{$nama}\" dihapus. Rombel yang tadinya memilih model ini kini mengikuti model pertama dalam daftar atau pola bawaan; jadwalnya tidak ikut terhapus.");
  }

  /** Validasi form model (dipakai simpan & buat baru). */
  private function validasiModel(Request $request): array
  {
    return $request->validate([
      'nama' => 'required|string|max:50',
      'hari' => 'required|array',
      'hari.*.jumlah_jam' => 'nullable|integer|min:0|max:12',
      'hari.*.mulai' => 'nullable|date_format:H:i',
      'hari.*.menit' => 'nullable|integer|min:15|max:90',
      'hari.*.istirahat.*.setelah' => 'nullable|integer|min:1|max:11',
      'hari.*.istirahat.*.menit' => 'nullable|integer|min:5|max:90',
      'hari.*.kegiatan.*.nama' => 'nullable|string|max:100',
      'hari.*.kegiatan.*.posisi' => 'nullable|in:sebelum,sesudah',
      'hari.*.kegiatan.*.jam' => 'nullable|integer|min:1|max:12',
      'hari.*.kegiatan.*.menit' => 'nullable|integer|min:1|max:180',
    ]);
  }

  /** Rangkai array kerangka dari data form yang tervalidasi. */
  private function kerangkaDariData(array $data): array
  {
    $kerangka = [];
    foreach (self::HARI as $hari => $nama) {
      $in = $data['hari'][$hari] ?? [];
      $jumlah = (int) ($in['jumlah_jam'] ?? 0);
      if ($jumlah <= 0) {
        continue; // hari tanpa pelajaran tidak masuk kerangka
      }

      $istirahat = [];
      foreach ($in['istirahat'] ?? [] as $jeda) {
        $setelah = (int) ($jeda['setelah'] ?? 0);
        $menit = (int) ($jeda['menit'] ?? 0);
        if ($setelah >= 1 && $setelah < $jumlah && $menit >= 5) {
          $istirahat[$setelah] = ['setelah' => $setelah, 'menit' => $menit];
        }
      }
      ksort($istirahat);

      // Kegiatan tetap hari itu (Upacara, Senam, Literasi, ...):
      // nama + posisi relatif ke jam pelajaran + durasi bebas.
      $kegiatan = [];
      foreach ($in['kegiatan'] ?? [] as $keg) {
        $namaK = trim((string) ($keg['nama'] ?? ''));
        $jamK = (int) ($keg['jam'] ?? 0);
        $menitK = (int) ($keg['menit'] ?? 0);
        if ($namaK !== '' && $menitK >= 1 && $jamK >= 1 && $jamK <= $jumlah) {
          $kegiatan[] = [
            'nama' => $namaK,
            'posisi' => ($keg['posisi'] ?? 'sebelum') === 'sesudah' ? 'sesudah' : 'sebelum',
            'jam' => $jamK,
            'menit' => $menitK,
          ];
        }
      }

      $kerangka[$hari] = [
        'jumlah_jam' => $jumlah,
        'mulai' => $in['mulai'] ?? '07:00',
        'menit' => (int) ($in['menit'] ?? 35),
        'istirahat' => array_values($istirahat),
        'kegiatan' => $kegiatan,
      ];
    }

    return $kerangka;
  }

  public function simpanPengaturan(Request $request, $id)
  {
    $model = $this->cariModel($id);
    $data = $this->validasiModel($request);

    $model->update([
      'nama' => $data['nama'],
      'kerangka' => $this->kerangkaDariData($data),
    ]);
    $this->memoModel = [];

    return redirect()
      ->route('jadwal-pelajaran.pengaturan.model', $model->id)
      ->with('success', "Model \"{$model->nama}\" tersimpan. Jadwal yang sudah terisi di luar kerangka baru akan ditandai di editor.");
  }

  /**
   * Halaman tambah model: formulir kerangka yang sama dengan
   * Atur Kerangka, tetapi untuk model BARU (nama + kerangka
   * diisi sekaligus, lalu disimpan sebagai model baru).
   */
  public function buatModel()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();

    $model = new JadwalPengaturan([
      'sekolah_id' => $sekolahId,
      'semester_id' => $semesterId,
      'nama' => '',
      'kerangka' => $this->kerangkaBawaan($sekolah),
    ]);

    return view('content.jadwal-pelajaran.pengaturan-model', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($semesterId),
      'model' => $model,
      'kerangka' => $this->kerangkaModel($model, $sekolah)['hari'],
      'tersimpan' => false,
      'namaHari' => self::HARI,
    ]);
  }

  public function simpanModelBaru(Request $request)
  {
    $data = $this->validasiModel($request);

    $sekolahId = $this->sekolahKonteksIdNullable();
    $semesterId = $this->semesterId();

    $model = JadwalPengaturan::create([
      'sekolah_id' => $sekolahId,
      'semester_id' => $semesterId,
      'nama' => $data['nama'],
      'kerangka' => $this->kerangkaDariData($data),
    ]);
    $this->memoModel = [];

    return redirect()
      ->route('jadwal-pelajaran.pengaturan')
      ->with('success', "Model \"{$model->nama}\" dibuat. Pilih model ini untuk rombel dari daftar rombel atau kepala editor.");
  }

  /** Kode mapel untuk kertas kerja: kode, lalu nama_singkat, lalu nama. */
  private function kodeMapel(MapelMapping $mapping): string
  {
    $master = $mapping->masterMapel;
    $kode = trim((string) ($master->kode ?? ''));
    if ($kode === '') {
      $kode = trim((string) ($master->nama_singkat ?? ''));
    }
    if ($kode === '') {
      $kode = trim((string) ($master->nama ?? '-'));
    }

    return $kode;
  }

  /**
   * Rombel-rombel yang SECARA EKSPLISIT memilih model ini di
   * editor (jadwal_pengaturan_id terisi). Untuk template &
   * impor Mark minta hanya rombel itu yang masuk — rombel
   * yang belum memilih model tidak ikut, walau di editor ia
   * tampil mengikuti model pertama sebagai bawaan.
   */
  private function rombelPemakaiModel(JadwalPengaturan $model, int $sekolahId, int $semesterId)
  {
    return Rombel::with('ptk')
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->where('jadwal_pengaturan_id', $model->id)
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get()
      ->values();
  }

  /**
   * Unduh template Excel impor jadwal untuk satu model.
   *
   * Tata letaknya MENYAMAI lembar kerja Mark ("JADWAL SD
   * NEGERI 01 TOTOREJO KELAS 3-6.FIX"): judul tiga baris,
   * baris catatan bantu, baris ID_ROMBEL, kepala dua baris
   * (HARI | WAKTU | JP | KELAS ... | KODE GURU ...), kolom
   * WAKTU satu kolom berisi rentang, kepala rombel berulang
   * di setiap blok hari, kolom HARI dan baris kegiatan
   * di-merge, serta kolom bantu daftar kode mapel di kanan.
   * Sheet Pengampu berbentuk MATRIKS mapel x rombel berisi
   * ID PTK pengampu untuk SEMUA mapel hasil mapping (sesuai
   * kurikulum tiap rombel); kolom kode guru terisi rumus
   * INDEX/MATCH dua arah dari matriks itu. Kolom bantu
   * (blok kode guru + daftar) dan baris catatan/ID
   * disembunyikan; sheet dilindungi kecuali kotak isian.
   *
   * Format bersyarat pada sel mapel: MERAH bila kode guru
   * baris itu ganda (bentrok), ABU bila kode tidak ada di
   * daftar bantu. PENTING: rumus format bersyarat ekspresi
   * wajib dipasang lewat setConditions() — setText() tidak
   * ditulis penulis Xlsx sehingga aturannya kosong.
   */
  public function templateImpor($id)
  {
    return $this->unduhBukuModel($id, false);
  }

  /**
   * Ekspor Excel: buku yang sama persis dengan template impor,
   * tetapi kotak pelajarannya SUDAH TERISI jadwal saat ini —
   * untuk arsip / dibagikan, bukan untuk diimpor ulang (walau
   * tetap bisa, isinya memang jadwal yang berlaku).
   */
  public function eksporExcel($id)
  {
    return $this->unduhBukuModel($id, true);
  }

  private function unduhBukuModel($id, bool $terisi)
  {
    $model = $this->cariModel($id);
    $sekolahId = (int) $model->sekolah_id;
    $semesterId = (int) $model->semester_id;
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semester = Semester::find($semesterId);

    $rombels = $this->rombelPemakaiModel($model, $sekolahId, $semesterId);
    if ($rombels->isEmpty()) {
      return back()->with('error', 'Belum ada rombel yang memakai model ini — pilih dulu modelnya dari editor rombel.');
    }

    $kerangkaHari = $this->kerangkaModel($model, $sekolah)['hari'];
    $pengampus = Pengampu::with(['mapping.masterMapel', 'ptk'])
      ->whereIn('rombel_id', $rombels->pluck('id'))
      ->where('semester_id', $semesterId)
      ->get();

    $n = $rombels->count();
    $kolKelasAwal = 4;                        // kolom D
    $kolKelasAkhir = $kolKelasAwal + $n - 1;
    $kolGuruAwal = $kolKelasAkhir + 2;        // satu kolom celah
    $kolGuruAkhir = $kolGuruAwal + $n - 1;
    $kolBantu = $kolGuruAkhir + 2;            // satu kolom celah
    $huruf = fn(int $i) => Coordinate::stringFromColumnIndex($i);
    $awalK = $huruf($kolKelasAwal);
    $akhirK = $huruf($kolKelasAkhir);
    $awalT = $huruf($kolGuruAwal);
    $akhirT = $huruf($kolGuruAkhir);
    $hurufBantu = $huruf($kolBantu);
    $hurufBantu2 = $huruf($kolBantu + 1);

    // Matriks pengampu: baris = SEMUA mapel hasil mapping
    // yang berlaku bagi rombel-rombel ini (sesuai kurikulum
    // tiap rombel, lewat getMapels yang juga dipakai halaman
    // Pengampu & editor), kolom = rombel, isi = ID PTK
    // pengampunya (kosong bila mapel tidak berlaku di rombel
    // itu atau belum ada pengampunya).
    $guruPerSel = [];
    foreach ($pengampus as $p) {
      $guruPerSel[$p->rombel_id][$p->mapel_mapping_id] = $p->ptk_id;
    }
    $mapelBaris = [];
    foreach ($rombels as $r) {
      foreach ($this->getMapels($sekolah, $r, $semesterId) as $m) {
        $mid = $m->mapel_master_id;
        if (!isset($mapelBaris[$mid])) {
          $mapelBaris[$mid] = [
            'nama' => $m->masterMapel->nama ?? '-',
            'kode' => $this->kodeMapel($m),
            'perRombel' => [],
          ];
        }
        $mapelBaris[$mid]['perRombel'][$r->id] = $guruPerSel[$r->id][$m->id] ?? null;
      }
    }
    $mapelBaris = collect($mapelBaris)->sortBy('kode')->values();

    // Mode ekspor: jadwal yang sudah tersimpan, untuk ditulis
    // ke kotak pelajaran (kode mapel / *kegiatan).
    $selJadwal = [];
    if ($terisi) {
      $selRows = JadwalPelajaran::with('pengampu.mapping')
        ->where('semester_id', $semesterId)
        ->whereIn('rombel_id', $rombels->pluck('id'))
        ->get();
      foreach ($selRows as $sj) {
        $selJadwal[$sj->rombel_id][$sj->hari][$sj->jam_ke] = $sj;
      }
    }
    $barisMatriksAkhir = 2 + $mapelBaris->count();
    $matAwal = $huruf(3);                     // kolom C di sheet Pengampu
    $matAkhir = $huruf(3 + $n - 1);

    // Daftar kode untuk kolom bantu (urut, unik).
    $daftarKode = $mapelBaris->map(fn($mb) => ['kode' => $mb['kode'], 'nama' => $mb['nama']])->unique('kode')->sortBy('kode')->values();
    $barisKodeAkhir = 7 + max(1, $daftarKode->count());

    $ss = new Spreadsheet();
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('Jadwal');
    $rentangJudul = 'A1:' . $akhirK . '1';

    // ── Judul tiga baris (baris 1-3), baris 4 kosong ──
    $romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    $tingkatMin = (int) $rombels->min('tingkat');
    $tingkatMax = (int) $rombels->max('tingkat');
    $judulKelas = 'KELAS ' . ($romawi[$tingkatMin] ?? $tingkatMin);
    if ($tingkatMax !== $tingkatMin) {
      $judulKelas .= ' SAMPAI ' . ($romawi[$tingkatMax] ?? $tingkatMax);
    }
    $sheet->mergeCells($rentangJudul);
    $sheet->setCellValue('A1', 'JADWAL PEMBELAJARAN ' . $judulKelas);
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->mergeCells('A2:' . $akhirK . '2');
    $sheet->setCellValue('A2', Str::upper($sekolah->detailTerbaru->nama_sekolah ?? ''));
    $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
    $sheet->mergeCells('A3:' . $akhirK . '3');
    $labelSemester = $semester
      ? 'SEMESTER ' . ($semester->semester === 'Genap' ? 'II' : 'I') . ' TAHUN PELAJARAN '
        . str_replace('/', '-', (string) $semester->tahun_pelajaran)
      : '';
    $sheet->setCellValue('A3', $labelSemester . ' — Model "' . $model->nama . '"');
    $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(11);
    $sheet->getStyle('A1:' . $akhirK . '3')->getAlignment()
      ->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // ── Baris 5: catatan bantu ──
    $sheet->mergeCells($awalT . '5:' . $akhirT . '5');
    $sheet->setCellValue(
      $awalT . '5',
      'kolom bantu untuk format bersyarat, jika dalam 1 baris di tabel ini yang isi cellnya sama maka cell di tabel jadwal berwarna merah'
    );
    $sheet->mergeCells($hurufBantu . '5:' . $hurufBantu2 . '5');
    $sheet->setCellValue($hurufBantu . '5', 'Tabel Bantu untuk mengisi mapel');
    $sheet->getStyle('A5:' . $hurufBantu . '5')->applyFromArray([
      'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '808080']],
      'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER],
    ]);
    $sheet->getRowDimension(5)->setRowHeight(26);

    // ── Baris 6: ID rombel ──
    $sheet->setCellValue('C6', 'ID_ROMBEL');
    foreach ($rombels as $i => $r) {
      $sheet->setCellValueByColumnAndRow($kolKelasAwal + $i, 6, $r->id);
      $sheet->setCellValueByColumnAndRow($kolGuruAwal + $i, 6, $r->id);
    }
    $sheet->mergeCells($hurufBantu . '6:' . $hurufBantu2 . '6');
    $sheet->setCellValue($hurufBantu . '6', 'Tabel Bantu Daftar Nama Mapel');
    $sheet->getStyle('A6:' . $hurufBantu . '6')->applyFromArray([
      'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '808080']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);

    // ── Baris 7: kepala kolom ──
    $sheet->setCellValue('A7', 'HARI');
    $sheet->setCellValue('B7', 'WAKTU');
    $sheet->setCellValue('C7', 'JP');
    $sheet->mergeCells($awalK . '7:' . $akhirK . '7');
    $sheet->setCellValue($awalK . '7', 'KELAS');
    $sheet->mergeCells($awalT . '7:' . $akhirT . '7');
    $sheet->setCellValue($awalT . '7', 'KODE GURU');
    $sheet->setCellValue($hurufBantu . '7', 'Kode');
    $sheet->setCellValue($hurufBantu2 . '7', 'Mapel');
    $sheet->getStyle('A7:' . $akhirK . '7')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '7367F0']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle($awalT . '7:' . $akhirT . '7')->applyFromArray([
      'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F59E0B']],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    $sheet->getStyle($hurufBantu . '7:' . $hurufBantu2 . '7')->getFont()->setBold(true);
    $sheet->mergeCells('A7:A8');
    $sheet->mergeCells('B7:B8');
    $sheet->mergeCells('C7:C8');
    $sheet->getStyle('A7:C8')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

    // ── Baris 8: nama rombel + awal daftar kode bantu ──
    foreach ($rombels as $i => $r) {
      $sheet->setCellValueByColumnAndRow($kolKelasAwal + $i, 8, $r->nama_rombel);
      $sheet->setCellValueByColumnAndRow($kolGuruAwal + $i, 8, $r->nama_rombel);
    }
    $sheet->getStyle($awalK . '8:' . $akhirT . '8')->applyFromArray([
      'font' => ['bold' => true],
      'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    ]);
    foreach ($daftarKode as $i => $pasangan) {
      $sheet->setCellValue($hurufBantu . (8 + $i), $pasangan['kode']);
      $sheet->setCellValue($hurufBantu2 . (8 + $i), $pasangan['nama']);
    }

    // Legenda warna format bersyarat, di bawah daftar kode:
    // contoh sel berwarna + keterangannya di kolom sebelah.
    $barisLegenda = $barisKodeAkhir + 2;
    $sheet->getStyle($hurufBantu . $barisLegenda)->applyFromArray([
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B7B7B7']],
    ]);
    $sheet->setCellValue($hurufBantu2 . $barisLegenda, 'Kode mapel tidak ada dalam daftar');
    $sheet->getStyle($hurufBantu . ($barisLegenda + 1))->applyFromArray([
      'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
    ]);
    $sheet->setCellValue($hurufBantu2 . ($barisLegenda + 1), 'Guru tabrakan jam (bentrok)');
    $sheet->getStyle($hurufBantu2 . $barisLegenda . ':' . $hurufBantu2 . ($barisLegenda + 1))
      ->getFont()->setSize(10);

    // ── Baris data mulai baris 9 ──
    $baris = 9;
    $hariUrut = array_keys($kerangkaHari);
    sort($hariUrut);
    $blokPertama = true;
    foreach ($hariUrut as $hari) {
      $frame = $kerangkaHari[$hari];
      $gw = $this->garisWaktu($frame);
      $jumlah = (int) $frame['jumlah_jam'];

      // Kepala rombel berulang di setiap blok hari berikutnya.
      if (!$blokPertama) {
        $sheet->setCellValueByColumnAndRow(1, $baris, 'HARI');
        $sheet->setCellValueByColumnAndRow(2, $baris, 'WAKTU');
        $sheet->setCellValueByColumnAndRow(3, $baris, 'JP');
        foreach ($rombels as $i => $r) {
          $sheet->setCellValueByColumnAndRow($kolKelasAwal + $i, $baris, $r->nama_rombel);
          $sheet->setCellValueByColumnAndRow($kolGuruAwal + $i, $baris, $r->nama_rombel);
        }
        $sheet->getStyle('A' . $baris . ':' . $akhirT . $baris)->applyFromArray([
          'font' => ['bold' => true, 'size' => 10],
          'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E7E6F5']],
          'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $baris++;
      }
      $blokPertama = false;
      $barisAwalHari = $baris;

      for ($anchor = 0; $anchor <= $jumlah; $anchor++) {
        foreach ($gw['lainnya'][$anchor] ?? [] as $ev) {
          // Kegiatan tetap / istirahat: nama di sel pertama,
          // kolom kelas DI-MERGE selebarnya.
          $sheet->setCellValueByColumnAndRow(2, $baris, str_replace(':', '.', $ev['waktu'][0]) . ' - ' . str_replace(':', '.', $ev['waktu'][1]));
          $sheet->setCellValueByColumnAndRow($kolKelasAwal, $baris, Str::upper($ev['nama']));
          if ($kolKelasAkhir > $kolKelasAwal) {
            $sheet->mergeCells($awalK . $baris . ':' . $akhirK . $baris);
          }
          $warna = $ev['jenis'] === 'kegiatan' ? 'FFF3D9' : 'ECEFF1';
          $sheet->getStyle('A' . $baris . ':' . $akhirK . $baris)->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $warna]],
            'font' => ['bold' => $ev['jenis'] === 'kegiatan', 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
          ]);
          $baris++;
        }

        if ($anchor < $jumlah) {
          $jam = $anchor + 1;
          $w = $gw['pelajaran'][$jam];
          $sheet->setCellValueByColumnAndRow(2, $baris, str_replace(':', '.', $w[0]) . ' - ' . str_replace(':', '.', $w[1]));
          $sheet->setCellValueByColumnAndRow(3, $baris, $jam);
          foreach ($rombels as $i => $r) {
            $kolJ = $huruf($kolKelasAwal + $i);
            $kolT = $huruf($kolGuruAwal + $i);
            $sheet->getStyle($kolJ . $baris)->applyFromArray([
              'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF8E1']],
              'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            // Satu-satunya sel yang boleh diedit saat sheet
            // dilindungi (lihat pengaktifan proteksi di bawah).
            $sheet->getCell($kolJ . $baris)->getStyle()->getProtection()
              ->setLocked(Protection::PROTECTION_UNPROTECTED);
            // Kode guru OTOMATIS dari matriks Pengampu:
            // INDEX dua arah (baris kode mapel x kolom rombel).
            $lookup = 'INDEX(Pengampu!$' . $matAwal . '$3:$' . $matAkhir . '$' . $barisMatriksAkhir
              . ',MATCH(UPPER(' . $kolJ . $baris . '),Pengampu!$B$3:$B$' . $barisMatriksAkhir
              . ',0),MATCH(' . $kolJ . '$6,Pengampu!$' . $matAwal . '$1:$' . $matAkhir . '$1,0))';
            $sheet->setCellValue(
              $kolT . $baris,
              '=IF(' . $kolJ . $baris . '="","",IF(LEFT(' . $kolJ . $baris . ',1)="*","",IF(IFERROR('
                . $lookup . ',"")="","??",' . $lookup . ')))'
            );
            $sheet->getStyle($kolT . $baris)->getAlignment()
              ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            if ($terisi) {
              $selIsi = $selJadwal[$r->id][$hari][$jam] ?? null;
              if ($selIsi) {
                $sheet->setCellValue($kolJ . $baris, $selIsi->pengampu
                  ? $this->kodeMapel($selIsi->pengampu->mapping)
                  : '*' . $selIsi->nama_kegiatan);
              }
            }
          }

          // Format bersyarat pada SEL MAPEL baris ini:
          // MERAH bila kode guru muncul >1x sebaris (bentrok);
          // ABU bila kode tidak ada di daftar bantu. Rumus
          // dipasang lewat setConditions() — BUKAN setText().
          $cfMerah = new Conditional();
          $cfMerah->setConditionType(Conditional::CONDITION_EXPRESSION);
          $cfMerah->setConditions(['AND(' . $awalT . $baris . '<>"",COUNTIF($'
            . $awalT . $baris . ':$' . $akhirT . $baris . ',' . $awalT . $baris . ')>1)']);
          $cfMerah->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FF0000');
          $cfMerah->getStyle()->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');

          $cfAbu = new Conditional();
          $cfAbu->setConditionType(Conditional::CONDITION_EXPRESSION);
          $cfAbu->setConditions(['AND(' . $awalK . $baris . '<>"",COUNTIF($' . $hurufBantu
            . '$8:$' . $hurufBantu . '$' . $barisKodeAkhir . ',' . $awalK . $baris
            . ')=0,LEFT(' . $awalK . $baris . ',1)<>"*")']);
          $cfAbu->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('B7B7B7');

          $sheet->getStyle($awalK . $baris . ':' . $akhirK . $baris)
            ->setConditionalStyles([$cfMerah, $cfAbu]);

          $baris++;
        }
      }

      // Kolom HARI di-merge sepanjang blok hari ini.
      $sheet->setCellValueByColumnAndRow(1, $barisAwalHari, Str::upper(self::HARI[$hari]));
      if ($baris - 1 > $barisAwalHari) {
        $sheet->mergeCells('A' . $barisAwalHari . ':A' . ($baris - 1));
      }
      $sheet->getStyle('A' . $barisAwalHari)->getAlignment()
        ->setVertical(Alignment::VERTICAL_CENTER);
    }
    $barisAkhir = $baris - 1;

    // Bingkai + lebar kolom + bekukan panel.
    $garis = [
      'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFC9D4']]],
    ];
    $sheet->getStyle('A7:' . $akhirT . $barisAkhir)->applyFromArray($garis);
    $sheet->getStyle('B9:B' . $barisAkhir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('C9:C' . $barisAkhir)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A9:A' . $barisAkhir)->getFont()->setBold(true);
    $sheet->getColumnDimension('A')->setWidth(11);
    $sheet->getColumnDimension('B')->setWidth(15);
    $sheet->getColumnDimension('C')->setWidth(6);
    for ($c = $kolKelasAwal; $c <= $kolKelasAkhir; $c++) {
      $sheet->getColumnDimension($huruf($c))->setWidth(13);
    }
    $sheet->getColumnDimension($huruf($kolKelasAkhir + 1))->setWidth(3);
    for ($c = $kolGuruAwal; $c <= $kolGuruAkhir; $c++) {
      $sheet->getColumnDimension($huruf($c))->setWidth(10);
    }
    $sheet->getColumnDimension($huruf($kolGuruAkhir + 1))->setWidth(3);
    $sheet->getColumnDimension($hurufBantu)->setWidth(13);
    $sheet->getColumnDimension($hurufBantu2)->setWidth(26);
    $sheet->freezePane($huruf($kolKelasAwal) . '9');

    // Sembunyikan bagian bantu: kolom celah, blok kode guru,
    // baris catatan (5) & ID (6). Kolom daftar bantu SENGAJA
    // tetap terlihat sebagai referensi saat mengetik. Rumus
    // dan format bersyarat tetap bekerja dari balik sembunyi;
    // pengguna dapat memunculkannya lagi lewat Unhide.
    foreach ([$kolKelasAkhir + 1] as $cSembunyi) {
      $sheet->getColumnDimension($huruf($cSembunyi))->setVisible(false);
    }
    for ($c = $kolGuruAwal; $c <= $kolGuruAkhir; $c++) {
      $sheet->getColumnDimension($huruf($c))->setVisible(false);
    }
    $sheet->getRowDimension(5)->setVisible(false);
    $sheet->getRowDimension(6)->setVisible(false);

    // Lindungi sheet Jadwal: hanya kotak kuning pelajaran
    // yang terbuka; struktur, rumus, dan daftar tidak bisa
    // terutak-atik tidak sengaja. Tanpa kata sandi.
    $sheet->getProtection()->setSheet(true);

    // ── Sheet Pengampu: MATRIKS mapel x rombel ──
    $ref = $ss->createSheet();
    $ref->setTitle('Pengampu');
    $ref->setCellValue('B1', 'ID_Rombel');
    foreach ($rombels as $i => $r) {
      $ref->setCellValueByColumnAndRow(3 + $i, 1, $r->id);
      $ref->setCellValueByColumnAndRow(3 + $i, 2, $r->nama_rombel);
    }
    $ref->setCellValue('A2', 'Nama Mapel');
    $ref->setCellValue('B2', 'Kode Mapel');
    $ref->getStyle('A1:' . $matAkhir . '2')->getFont()->setBold(true);
    $rb = 3;
    foreach ($mapelBaris as $mb) {
      $ref->setCellValue('A' . $rb, $mb['nama']);
      $ref->setCellValue('B' . $rb, $mb['kode']);
      foreach ($rombels as $i => $r) {
        $kg = $mb['perRombel'][$r->id] ?? null;
        if ($kg !== null && $kg !== '') {
          $ref->setCellValueByColumnAndRow(3 + $i, $rb, $kg);
        }
      }
      $rb++;
    }
    $ref->getColumnDimension('A')->setWidth(28);
    $ref->getColumnDimension('B')->setWidth(13);
    for ($c = 3; $c < 3 + $n; $c++) {
      $ref->getColumnDimension($huruf($c))->setWidth(11);
    }
    $ref->getStyle('A1:' . $matAkhir . ($rb - 1))->applyFromArray($garis);
    // Baris ID rombel disembunyikan (tetap dipakai rumus) dan
    // seluruh sheet dilindungi — matriks ini referensi, bukan
    // untuk diedit tangan (pengampu diubah dari aplikasinya).
    $ref->getRowDimension(1)->setVisible(false);
    $ref->getProtection()->setSheet(true);

    // ── Sheet Petunjuk ──
    $petunjuk = $ss->createSheet();
    $petunjuk->setTitle('Petunjuk');
    $petunjuk->getColumnDimension('A')->setWidth(115);
    $barisPetunjuk = [
      'PETUNJUK IMPOR JADWAL PELAJARAN',
      '',
      '1. Isi HANYA kotak kuning pada kolom kelas: ketik KODE MAPEL persis seperti kolom "Kode Mapel" di sheet Pengampu / daftar bantu di kanan (mis. MTK, PKN, PJOK). Huruf besar/kecil tidak masalah.',
      '2. Untuk kegiatan non-mapel (Pramuka, Kokurikuler, ...), awali dengan tanda bintang: *Pramuka.',
      '3. Kotak yang dikosongkan berarti jam itu kosong — jadwal lama pada jam tersebut akan dihapus saat impor.',
      '4. Kolom KODE GURU terisi otomatis dari sheet Pengampu. Sel mapel berwarna MERAH bila guru yang sama mengajar di dua kelas pada jam yang sama (bentrok) — perbaiki sebelum mengimpor. Sel mapel berwarna ABU bila kode yang diketik tidak ada di daftar bantu. Tanda "??" pada kode guru berarti mapel itu tidak diajarkan guru mana pun di kelas tersebut.',
      '5. Baris berwarna (UPACARA, LITERASI, ISTIRAHAT, ...) berasal dari kerangka model dan tidak perlu diubah; baris itu dilewati saat impor.',
      '6. Jangan mengubah baris 1-8, nama sheet, atau urutan kolom. Simpan tetap sebagai .xlsx, lalu unggah lewat tombol Impor pada model ini di halaman Model Jadwal.',
      '7. Bila ada kesalahan saat impor, TIDAK ada data yang ditulis — semua diperbaiki dulu, baru impor ulang.',
      '8. Kolom kode guru sengaja disembunyikan agar tabel bersih — rumus dan peringatan warnanya tetap bekerja; bila perlu, tampilkan lagi lewat Unhide di Excel. Kolom daftar bantu di kanan tetap terlihat sebagai referensi kode mapel. Sheet dilindungi: hanya kotak kuning yang bisa diedit (proteksi tanpa kata sandi, bisa dibuka dari menu Review bila terpaksa). Warna abu-abu hanya peringatan saat mengetik; aplikasi tetap memeriksa ulang semuanya saat impor.',
    ];
    foreach ($barisPetunjuk as $i => $teks) {
      $petunjuk->setCellValue('A' . ($i + 1), $teks);
    }
    $petunjuk->getStyle('A1')->getFont()->setBold(true)->setSize(13);
    $petunjuk->getStyle('A1:A' . count($barisPetunjuk))->getAlignment()->setWrapText(true);
    $petunjuk->getProtection()->setSheet(true);

    $ss->setActiveSheetIndex(0);
    $namaFile = ($terisi ? 'jadwal-terisi-' : 'template-jadwal-') . Str::slug($model->nama) . '.xlsx';
    $path = tempnam(sys_get_temp_dir(), 'tpl-jadwal-') . '.xlsx';
    (new XlsxWriter($ss))->save($path);

    return response()->download($path, $namaFile)->deleteFileAfterSend(true);
  }

  /**
   * Impor jadwal dari template Excel model ini. Semantiknya
   * MENGGANTI isi kotak dalam kerangka untuk rombel-rombel di
   * template: kotak kosong = dikosongkan. Semua divalidasi
   * dulu (kode mapel dikenal & berpengampu, guru tidak
   * bentrok di dalam berkas maupun dengan rombel lain); satu
   * kesalahan saja membatalkan seluruh impor.
   */
  public function imporJadwal(Request $request, $id)
  {
    $model = $this->cariModel($id);
    $request->validate(['file' => 'required|file|mimes:xlsx|max:5120']);
    $sekolahId = (int) $model->sekolah_id;
    $semesterId = (int) $model->semester_id;
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);

    try {
      $wb = IOFactory::load($request->file('file')->getRealPath());
    } catch (\Throwable $e) {
      return back()->with('error', 'Berkas tidak bisa dibaca sebagai Excel (.xlsx).');
    }
    $sheet = $wb->getSheetByName('Jadwal');
    if (!$sheet) {
      return back()->with('error', 'Sheet "Jadwal" tidak ditemukan — pakai template hasil unduhan dari halaman ini.');
    }

    // Peta kolom -> rombel dari baris ID_ROMBEL (baris 6),
    // blok kelas mulai kolom D.
    $petaKolom = [];
    $kolMaks = Coordinate::columnIndexFromString($sheet->getHighestColumn());
    for ($c = 4; $c <= $kolMaks; $c++) {
      $v = trim((string) $sheet->getCellByColumnAndRow($c, 6)->getValue());
      if ($v === '' || !is_numeric($v)) {
        break;
      }
      $petaKolom[$c] = (int) $v;
    }
    if ($petaKolom === []) {
      return back()->with('error', 'Struktur template tidak dikenal (baris ID rombel kosong). Unduh template baru.');
    }

    $rombelIds = array_values(array_unique(array_values($petaKolom)));
    $rombels = Rombel::whereIn('id', $rombelIds)->get()->keyBy('id');
    $kesalahan = [];
    foreach ($rombelIds as $rid) {
      $r = $rombels->get($rid);
      if (!$r || (int) $r->sekolah_id !== $sekolahId || (int) $r->semester_id !== $semesterId) {
        $kesalahan[] = "Rombel ID {$rid} di template tidak termasuk sekolah/semester ini.";
        continue;
      }
      $m = $this->modelUntuk($sekolahId, $semesterId, $r);
      if (!$m || $m->id !== $model->id) {
        $kesalahan[] = "Rombel {$r->nama_rombel} belum memilih model \"{$model->nama}\" di editor — unduh template baru.";
      }
    }
    if ($kesalahan) {
      return back()->with('impor_errors', $kesalahan)->with('impor_model', $model->id)
        ->with('error', 'Impor dibatalkan — tidak ada data yang ditulis.');
    }

    // Peta kode mapel per rombel dari pengampu.
    $pengampus = Pengampu::with(['mapping.masterMapel', 'ptk'])
      ->whereIn('rombel_id', $rombelIds)
      ->where('semester_id', $semesterId)
      ->get();
    $petaKode = [];
    foreach ($pengampus as $p) {
      $kandidat = [
        Str::upper($this->kodeMapel($p->mapping)),
        Str::upper((string) ($p->mapping->masterMapel->nama_singkat ?? '')),
        Str::upper((string) ($p->mapping->masterMapel->nama ?? '')),
      ];
      foreach ($kandidat as $kunciKode) {
        if ($kunciKode !== '' && !isset($petaKode[$p->rombel_id][$kunciKode])) {
          $petaKode[$p->rombel_id][$kunciKode] = $p;
        }
      }
    }

    // Baca kotak-kotak dari sheet.
    $petaHari = [];
    foreach (self::HARI as $no => $namaH) {
      $petaHari[Str::upper($namaH)] = $no;
    }
    $ops = [];
    $barisMaks = $sheet->getHighestRow();
    $hariAktif = null;
    for ($row = 9; $row <= $barisMaks; $row++) {
      // Kolom HARI di-merge per blok hari: nilai hanya ada di
      // baris pertama blok, baris berikutnya meneruskan hari
      // itu. Baris kepala berulang ("HARI") diabaikan karena
      // bukan nama hari.
      $namaHari = Str::upper(trim((string) $sheet->getCellByColumnAndRow(1, $row)->getValue()));
      if ($namaHari !== '' && isset($petaHari[$namaHari])) {
        $hariAktif = $petaHari[$namaHari];
      }
      $hari = $hariAktif;
      $jamMentah = $sheet->getCellByColumnAndRow(3, $row)->getValue();
      if (!$hari || !is_numeric($jamMentah) || (int) $jamMentah < 1) {
        continue;
      }
      $jam = (int) $jamMentah;
      foreach ($petaKolom as $c => $rid) {
        $mentah = trim((string) $sheet->getCellByColumnAndRow($c, $row)->getValue());
        if ($mentah === '') {
          continue;
        }
        if (Str::startsWith($mentah, '*')) {
          $namaK = trim(Str::substr($mentah, 1));
          if ($namaK !== '') {
            $ops[$rid][$hari][$jam] = ['tipe' => 'kegiatan', 'nama' => Str::limit($namaK, 100, '')];
          }
          continue;
        }
        $p = $petaKode[$rid][Str::upper($mentah)] ?? null;
        if (!$p) {
          $kesalahan[] = self::HARI[$hari] . " jam {$jam}, rombel {$rombels[$rid]->nama_rombel}: kode \"{$mentah}\" tidak dikenal / mapelnya belum ada pengampu.";
          continue;
        }
        $ops[$rid][$hari][$jam] = ['tipe' => 'pelajaran', 'pengampu' => $p];
      }
    }

    // Bentrok guru di dalam berkas ini.
    $slot = [];
    foreach ($ops as $rid => $perHari) {
      foreach ($perHari as $hari => $perJam) {
        foreach ($perJam as $jam => $op) {
          if ($op['tipe'] !== 'pelajaran') {
            continue;
          }
          $ptkId = $op['pengampu']->ptk_id;
          $k = $hari . '-' . $jam;
          if (isset($slot[$k][$ptkId])) {
            $kesalahan[] = self::HARI[$hari] . " jam {$jam}: guru {$op['pengampu']->ptk->nama_ptk} bentrok — mengajar di {$slot[$k][$ptkId]} dan {$rombels[$rid]->nama_rombel}.";
          } else {
            $slot[$k][$ptkId] = $rombels[$rid]->nama_rombel;
          }
        }
      }
    }

    // Bentrok dengan jadwal rombel lain di sekolah ini.
    if ($kesalahan === []) {
      $rombelLainIds = Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)
        ->whereNotIn('id', $rombelIds)
        ->pluck('id');
      $lain = JadwalPelajaran::with(['pengampu.ptk', 'rombel'])
        ->whereIn('rombel_id', $rombelLainIds)
        ->whereNotNull('pengampu_id')
        ->get();
      $slotLain = [];
      foreach ($lain as $j) {
        $slotLain[$j->hari . '-' . $j->jam_ke][$j->pengampu->ptk_id] = $j->rombel->nama_rombel;
      }
      foreach ($ops as $rid => $perHari) {
        foreach ($perHari as $hari => $perJam) {
          foreach ($perJam as $jam => $op) {
            if ($op['tipe'] !== 'pelajaran') {
              continue;
            }
            $ptkId = $op['pengampu']->ptk_id;
            $k = $hari . '-' . $jam;
            if (isset($slotLain[$k][$ptkId])) {
              $kesalahan[] = self::HARI[$hari] . " jam {$jam}, rombel {$rombels[$rid]->nama_rombel}: guru {$op['pengampu']->ptk->nama_ptk} sudah mengajar di rombel {$slotLain[$k][$ptkId]} pada jam yang sama.";
            }
          }
        }
      }
    }

    if ($kesalahan) {
      return back()->with('impor_errors', array_slice($kesalahan, 0, 30))->with('impor_model', $model->id)
        ->with('error', 'Impor dibatalkan — tidak ada data yang ditulis. Perbaiki dulu:');
    }

    $kerangkaHari = $this->kerangkaModel($model, $sekolah)['hari'];
    $terisi = 0;
    $kegiatan = 0;
    $dikosongkan = 0;
    DB::transaction(function () use ($ops, $rombelIds, $kerangkaHari, $semesterId, $sekolahId, &$terisi, &$kegiatan, &$dikosongkan) {
      foreach ($rombelIds as $rid) {
        foreach ($kerangkaHari as $hari => $frame) {
          for ($jam = 1; $jam <= (int) $frame['jumlah_jam']; $jam++) {
            $op = $ops[$rid][$hari][$jam] ?? null;
            $kunci = [
              'rombel_id' => $rid,
              'semester_id' => $semesterId,
              'hari' => $hari,
              'jam_ke' => $jam,
            ];
            if (!$op) {
              $dikosongkan += JadwalPelajaran::where($kunci)->delete();
              continue;
            }
            if ($op['tipe'] === 'pelajaran') {
              JadwalPelajaran::updateOrCreate($kunci, [
                'sekolah_id' => $sekolahId,
                'pengampu_id' => $op['pengampu']->id,
                'nama_kegiatan' => null,
                'pembina_id' => null,
              ]);
              $terisi++;
            } else {
              JadwalPelajaran::updateOrCreate($kunci, [
                'sekolah_id' => $sekolahId,
                'pengampu_id' => null,
                'nama_kegiatan' => $op['nama'],
                'pembina_id' => null,
              ]);
              $kegiatan++;
            }
          }
        }
      }
    });

    return back()->with('success', "Impor jadwal model \"{$model->nama}\" berhasil: {$terisi} kotak pelajaran terisi, {$kegiatan} kegiatan, {$dikosongkan} kotak dikosongkan.");
  }

  /**
   * Blok-blok satu rombel per hari untuk cetakan: pelajaran,
   * istirahat, dan kegiatan tetap digabung lalu diurutkan
   * menurut jam mulai, dari kerangka model rombel itu sendiri.
   * Hasil: [nomorHari => ['nama' =>, 'blok' => [...]]].
   */
  private function blokJadwalRombel(Sekolah $sekolah, int $semesterId, Rombel $rombel): array
  {
    $frame = $this->kerangka($sekolah, $semesterId, $rombel);
    $selPerHari = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->get()
      ->groupBy('hari');

    $hasil = [];
    foreach ($frame['hari'] as $hari => $f) {
      $gw = $this->garisWaktu($f);
      $selHari = ($selPerHari->get($hari) ?? collect())->keyBy('jam_ke');
      $blok = [];
      foreach ($gw['pelajaran'] as $jam => $w) {
        $c = $selHari->get($jam);
        $blok[] = [
          'mulai' => $w[0],
          'selesai' => $w[1],
          'jenis' => 'pelajaran',
          'label' => $c?->pengampu
            ? ($c->pengampu->mapping?->masterMapel?->nama ?? '-')
            : ($c?->nama_kegiatan ?? ''),
          'sub' => $c?->pengampu
            ? ($c->pengampu->ptk?->nama_lengkap ?? '')
            : ($c?->pembina?->nama_lengkap ?? ''),
        ];
      }
      foreach ($gw['lainnya'] as $evs) {
        foreach ($evs as $ev) {
          $blok[] = [
            'mulai' => $ev['waktu'][0],
            'selesai' => $ev['waktu'][1],
            'jenis' => $ev['jenis'],
            'label' => $ev['nama'],
            'sub' => '',
          ];
        }
      }
      usort($blok, fn($a, $b) => strcmp($a['mulai'], $b['mulai']));
      $hasil[$hari] = ['nama' => self::HARI[$hari] ?? (string) $hari, 'blok' => $blok];
    }
    return $hasil;
  }

  /** Cetak PDF jadwal per kelas: satu lembar per rombel. */
  public function eksporPdfKelas()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Pilih sekolah dulu lewat pemilih konteks.');
    }
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $semester = Semester::find($semesterId);

    $rombels = Rombel::with(['ptk', 'jadwalPengaturan'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
      ->get();

    $lembar = $rombels->map(function ($r) use ($sekolah, $semesterId) {
      $hari = $this->blokJadwalRombel($sekolah, $semesterId, $r);
      return [
        'rombel' => $r,
        'model' => $this->kerangka($sekolah, $semesterId, $r)['model']?->nama,
        'hari' => $hari,
        'baris' => collect($hari)->max(fn($h) => count($h['blok'])) ?? 0,
      ];
    });

    $pdf = Pdf::setPaper('a4', 'landscape');
    $pdf->setOption(['defaultFont' => 'sans-serif']);
    $pdf->loadView('content.jadwal-pelajaran.pdf-kelas', [
      'sekolah' => $sekolah,
      'semester' => $semester,
      'lembar' => $lembar,
    ]);
    return $pdf->download('jadwal-per-kelas.pdf');
  }

  /** Cetak PDF jadwal per guru: satu lembar per guru pengampu / pembina. */
  public function eksporPdfGuru()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    if (!$sekolahId) {
      return back()->with('error', 'Pilih sekolah dulu lewat pemilih konteks.');
    }
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $semester = Semester::find($semesterId);

    $rombels = Rombel::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->get()
      ->keyBy('id');

    // Garis waktu per rombel+hari (cache) untuk rentang waktunya.
    $gwCache = [];
    $rentang = function (Rombel $r, int $hari, int $jam) use (&$gwCache, $sekolah, $semesterId) {
      if (!isset($gwCache[$r->id][$hari])) {
        $frame = $this->kerangka($sekolah, $semesterId, $r)['hari'][$hari] ?? null;
        $gwCache[$r->id][$hari] = $frame ? $this->garisWaktu($frame) : null;
      }
      return $gwCache[$r->id][$hari]['pelajaran'][$jam] ?? null;
    };

    $baris = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->get();

    $perGuru = [];
    foreach ($baris as $b) {
      $rombel = $rombels->get($b->rombel_id);
      if (!$rombel) {
        continue;
      }
      $w = $rentang($rombel, (int) $b->hari, (int) $b->jam_ke);
      if (!$w) {
        continue;
      }
      $ptk = $b->pengampu?->ptk ?? $b->pembina;
      if (!$ptk) {
        continue;
      }
      $perGuru[$ptk->id]['ptk'] = $ptk;
      $perGuru[$ptk->id]['hari'][(int) $b->hari]['nama'] = self::HARI[(int) $b->hari] ?? (string) $b->hari;
      $perGuru[$ptk->id]['hari'][(int) $b->hari]['blok'][] = [
        'mulai' => $w[0],
        'selesai' => $w[1],
        'jenis' => 'pelajaran',
        'label' => $rombel->nama_rombel,
        'sub' => $b->pengampu
          ? ($b->pengampu->mapping?->masterMapel?->nama ?? '-')
          : ('Kegiatan: ' . ($b->nama_kegiatan ?? '-')),
      ];
    }

    $lembar = collect($perGuru)
      ->sortBy(fn($g) => $g['ptk']->nama_ptk)
      ->map(function ($g) {
        $hari = collect($g['hari'])->sortKeys()
          ->map(function ($h) {
            $blok = $h['blok'];
            usort($blok, fn($a, $b) => strcmp($a['mulai'], $b['mulai']));
            return ['nama' => $h['nama'], 'blok' => $blok];
          });
        return [
          'ptk' => $g['ptk'],
          'hari' => $hari->all(),
          'baris' => $hari->max(fn($h) => count($h['blok'])) ?? 0,
        ];
      })
      ->values();

    $pdf = Pdf::setPaper('a4', 'landscape');
    $pdf->setOption(['defaultFont' => 'sans-serif']);
    $pdf->loadView('content.jadwal-pelajaran.pdf-guru', [
      'sekolah' => $sekolah,
      'semester' => $semester,
      'lembar' => $lembar,
    ]);
    return $pdf->download('jadwal-per-guru.pdf');
  }

  /**
   * Operator memilih model jadwal untuk satu rombel (dari
   * kepala editor). Jadwal yang sudah terisi TIDAK dihapus:
   * kotak yang masih masuk kerangka baru tetap berlaku, kotak
   * yang jatuh di luar kerangka baru ditandai di editor
   * (daftar "di luar kerangka") untuk ditinjau Operator.
   */
  public function pilihModel(Request $request, Rombel $rombel)
  {
    $sekolahId = $this->jagaRombel($rombel);
    $sekolah = Sekolah::findOrFail($sekolahId);
    $semesterId = $this->semesterId();

    $data = $request->validate([
      'pengaturan_id' => 'required|integer',
    ]);

    $modelBaru = $this->daftarModel($sekolahId, $semesterId)
      ->firstWhere('id', (int) $data['pengaturan_id']);
    if (!$modelBaru) {
      return back()->with('error', 'Model jadwal tidak ditemukan untuk sekolah/semester ini.');
    }

    $rombel->update(['jadwal_pengaturan_id' => $modelBaru->id]);

    $hari = $this->kerangka($sekolah, $semesterId, $rombel)['hari'];
    $luar = JadwalPelajaran::where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->get(['hari', 'jam_ke'])
      ->filter(fn($b) => !isset($hari[$b->hari])
        || $b->jam_ke > (int) ($hari[$b->hari]['jumlah_jam'] ?? 0))
      ->count();

    return back()->with('success', "Model jadwal diganti ke \"{$modelBaru->nama}\"."
      . ($luar
        ? " {$luar} kotak berada di luar kerangka baru — lihat daftar peringatan di editor untuk meninjaunya."
        : ' Jadwal yang sudah terisi tetap berlaku mengikuti kerangka baru.'));
  }

  // ── Pengampu per rombel ───────────────────────────────────────
  public function pengampu(Rombel $rombel)
  {
    $sekolahId = $this->jagaRombel($rombel);
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();

    $mappings = $this->getMapels($sekolah, $rombel, $semesterId);
    $terpilih = Pengampu::with('ptk')
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->get()
      ->keyBy('mapel_mapping_id');
    $terplot = JadwalPelajaran::where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->selectRaw('pengampu_id, COUNT(*) AS c')
      ->groupBy('pengampu_id')
      ->pluck('c', 'pengampu_id');
    $gurus = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->orderBy('nama_ptk')
      ->get();

    return view('content.jadwal-pelajaran.pengampu', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($semesterId),
      'rombel' => $rombel,
      'mappings' => $mappings,
      'terpilih' => $terpilih,
      'terplot' => $terplot,
      'gurus' => $gurus,
    ]);
  }

  public function simpanPengampu(Request $request, Rombel $rombel)
  {
    $sekolahId = $this->jagaRombel($rombel);
    $sekolah = Sekolah::findOrFail($sekolahId);
    $semesterId = $this->semesterId();

    $data = $request->validate([
      'pengampu' => 'nullable|array',
      'pengampu.*' => 'nullable|integer',
    ]);
    $pilihan = $data['pengampu'] ?? [];

    $guruSah = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->pluck('id');

    $kotakTerhapus = 0;
    foreach ($this->getMapels($sekolah, $rombel, $semesterId) as $mapping) {
      $ptkId = (int) ($pilihan[$mapping->id] ?? 0);
      $lama = Pengampu::where('rombel_id', $rombel->id)
        ->where('mapel_mapping_id', $mapping->id)
        ->where('semester_id', $semesterId)
        ->first();

      if ($ptkId > 0) {
        if (!$guruSah->contains($ptkId)) {
          return back()->with('error', 'Ada guru yang bukan PTK aktif sekolah ini — pengampu tidak disimpan.');
        }
        Pengampu::updateOrCreate(
          [
            'rombel_id' => $rombel->id,
            'mapel_mapping_id' => $mapping->id,
            'semester_id' => $semesterId,
          ],
          ['sekolah_id' => $sekolahId, 'ptk_id' => $ptkId]
        );
      } elseif ($lama) {
        // Pengampu dikosongkan: kotak jadwalnya ikut terhapus
        // (FK cascade) — hitung dulu untuk dilaporkan.
        $kotakTerhapus += JadwalPelajaran::where('pengampu_id', $lama->id)->count();
        $lama->delete();
      }
    }

    $pesan = 'Pengampu tersimpan.';
    if ($kotakTerhapus > 0) {
      $pesan .= " {$kotakTerhapus} kotak jadwal ikut terhapus karena pengampunya dikosongkan.";
    }

    return redirect()
      ->route('jadwal-pelajaran.pengampu', $rombel)
      ->with('success', $pesan);
  }

  // ── Editor kisi jadwal ────────────────────────────────────────
  public function editor(Rombel $rombel)
  {
    $user = Auth::user();
    $sekolahId = $this->jagaRombel($rombel);
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $kerangka = $this->kerangka($sekolah, $semesterId, $rombel);
    $hariFrame = $kerangka['hari'];
    $hariAktif = array_keys($hariFrame);
    sort($hariAktif);
    $maxJam = $hariFrame
      ? max(array_map(fn($f) => (int) $f['jumlah_jam'], $hariFrame))
      : 0;

    // Garis waktu per hari dari kerangka: jam tampil pelajaran
    // plus SEMUA kejadian non-pelajaran (kegiatan tetap seperti
    // Upacara/Senam DAN istirahat) per jangkar — editor menggambar
    // kolom hari sebagai tumpukan blok yang tingginya sebanding
    // dengan durasi masing-masing.
    $waktu = [];
    $lainnya = [];
    foreach ($hariFrame as $hari => $frame) {
      $gw = $this->garisWaktu($frame);
      $waktu[$hari] = $gw['pelajaran'];
      $lainnya[$hari] = $gw['lainnya'];
    }

    $baris = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->get();
    $sel = [];
    foreach ($baris as $b) {
      $sel[$b->hari][$b->jam_ke] = $b;
    }

    $pengampuList = Pengampu::with(['mapping.masterMapel', 'ptk'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->get();
    $terplot = $baris->countBy('pengampu_id');

    // Kotak yatim: jadwal lama yang kini jatuh di luar kerangka
    // (hari dipangkas / jumlah jam dikurangi). Tidak dihapus
    // diam-diam — ditampilkan agar Operator memutuskan sendiri.
    $luarKerangka = $baris->filter(function ($b) use ($hariFrame) {
      return !isset($hariFrame[$b->hari])
        || $b->jam_ke > (int) $hariFrame[$b->hari]['jumlah_jam'];
    });

    // Peta kesibukan guru di rombel LAIN pada semester yang sama,
    // untuk menandai pilihan yang akan bentrok di editor.
    $sibuk = [];
    $jadwalLain = JadwalPelajaran::with(['pengampu', 'rombel'])
      ->where('semester_id', $semesterId)
      ->where('sekolah_id', $sekolahId)
      ->where('rombel_id', '!=', $rombel->id)
      ->get();
    foreach ($jadwalLain as $j) {
      if ($j->pengampu) {
        $sibuk[$j->hari][$j->jam_ke][] = [
          'ptk_id' => $j->pengampu->ptk_id,
          'rombel' => $j->rombel->nama_rombel,
        ];
      }
      if ($j->pembina_id) {
        $sibuk[$j->hari][$j->jam_ke][] = [
          'ptk_id' => $j->pembina_id,
          'rombel' => $j->rombel->nama_rombel,
        ];
      }
    }

    $guruList = Ptk::where('sekolah_id', $sekolahId)
      ->where('status', 'aktif')
      ->orderBy('nama_ptk')
      ->get();

    return view('content.jadwal-pelajaran.editor', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($semesterId),
      'rombel' => $rombel,
      'hariFrame' => $hariFrame,
      'hariAktif' => $hariAktif,
      'namaHari' => self::HARI,
      'maxJam' => $maxJam,
      'waktu' => $waktu,
      'sel' => $sel,
      'lainnya' => $lainnya,
      'guruList' => $guruList,
      'pengampuList' => $pengampuList,
      'terplot' => $terplot,
      'luarKerangka' => $luarKerangka,
      'sibuk' => $sibuk,
      'tersimpan' => $kerangka['tersimpan'],
      'modelJadwal' => $kerangka['model'],
      'daftarModel' => $this->daftarModel($sekolahId, $semesterId),
      'totalBaris' => $baris->count(),
      'bolehEdit' => $user->hasRole('Operator Sekolah'),
    ]);
  }

  // ── Simpan / hapus satu kotak (AJAX editor) ───────────────────
  public function simpanSel(Request $request)
  {
    $data = $request->validate([
      'rombel_id' => 'required|integer',
      'pengampu_id' => 'nullable|integer',
      'nama_kegiatan' => 'nullable|string|max:100',
      'pembina_id' => 'nullable|integer',
      'hari' => 'required|integer|min:1|max:6',
      'jam_ke' => 'required|integer|min:1|max:12',
    ]);

    $sekolahId = $this->sekolahKonteksIdNullable();
    $semesterId = $this->semesterId();

    $rombel = Rombel::where('id', $data['rombel_id'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->first();
    if (!$rombel) {
      return response()->json(['message' => 'Rombel tidak ditemukan di sekolah/semester ini.'], 422);
    }

    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $frame = $this->kerangka($sekolah, $semesterId, $rombel)['hari'][$data['hari']] ?? null;
    if (!$frame || $data['jam_ke'] > (int) $frame['jumlah_jam']) {
      return response()->json(['message' => 'Kotak itu di luar kerangka jam hari tersebut.'], 422);
    }

    $modeKegiatan = !empty($data['nama_kegiatan']);

    if ($modeKegiatan) {
      return $this->simpanSelKegiatan($data, $rombel, $sekolahId, $semesterId);
    }

    if (empty($data['pengampu_id'])) {
      return response()->json(['message' => 'Pilih pelajaran atau isi nama kegiatan dulu.'], 422);
    }

    $pengampu = Pengampu::with(['mapping.masterMapel', 'ptk'])
      ->where('id', $data['pengampu_id'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->first();
    if (!$pengampu) {
      return response()->json(['message' => 'Pengampu tidak berlaku untuk rombel ini. Tetapkan dulu pengampunya.'], 422);
    }

    // Penjaga bentrok guru: guru yang sama tidak boleh mengajar di
    // dua rombel pada hari+jam yang sama (keputusan: BLOKIR).
    if ($tabrakan = $this->cariTabrakanPtk($pengampu->ptk_id, $data, $rombel, $semesterId)) {
      return response()->json([
        'message' => "Bentrok: {$pengampu->ptk->nama_ptk} sedang bertugas di {$tabrakan->rombel->nama_rombel} pada waktu yang sama.",
      ], 422);
    }

    JadwalPelajaran::updateOrCreate(
      [
        'rombel_id' => $rombel->id,
        'semester_id' => $semesterId,
        'hari' => $data['hari'],
        'jam_ke' => $data['jam_ke'],
      ],
      [
        'sekolah_id' => $sekolahId,
        'pengampu_id' => $pengampu->id,
        'nama_kegiatan' => null,
        'pembina_id' => null,
      ]
    );

    return response()->json([
      'ok' => true,
      'tipe' => 'pelajaran',
      'pengampu_id' => $pengampu->id,
      'mapel' => $pengampu->mapping->masterMapel->nama ?? '-',
      'singkat' => $pengampu->mapping->masterMapel->nama_singkat ?: ($pengampu->mapping->masterMapel->nama ?? '-'),
      'guru' => $pengampu->ptk->nama_ptk ?? '-',
    ]);
  }

  /** Simpan kotak berisi KEGIATAN (Pramuka, Kokurikuler, ...). */
  private function simpanSelKegiatan(array $data, Rombel $rombel, int $sekolahId, int $semesterId)
  {
    $pembina = null;
    if (!empty($data['pembina_id'])) {
      $pembina = Ptk::where('id', $data['pembina_id'])
        ->where('sekolah_id', $sekolahId)
        ->where('status', 'aktif')
        ->first();
      if (!$pembina) {
        return response()->json(['message' => 'Pembina bukan PTK aktif sekolah ini.'], 422);
      }
      if ($tabrakan = $this->cariTabrakanPtk($pembina->id, $data, $rombel, $semesterId)) {
        return response()->json([
          'message' => "Bentrok: {$pembina->nama_ptk} sedang bertugas di {$tabrakan->rombel->nama_rombel} pada waktu yang sama.",
        ], 422);
      }
    }

    JadwalPelajaran::updateOrCreate(
      [
        'rombel_id' => $rombel->id,
        'semester_id' => $semesterId,
        'hari' => $data['hari'],
        'jam_ke' => $data['jam_ke'],
      ],
      [
        'sekolah_id' => $sekolahId,
        'pengampu_id' => null,
        'nama_kegiatan' => trim($data['nama_kegiatan']),
        'pembina_id' => $pembina?->id,
      ]
    );

    return response()->json([
      'ok' => true,
      'tipe' => 'kegiatan',
      'nama_kegiatan' => trim($data['nama_kegiatan']),
      'pembina_id' => $pembina?->id,
      'pembina' => $pembina?->nama_ptk ?? '',
    ]);
  }

  /**
   * Cari jadwal di rombel LAIN pada semester+hari+jam yang sama
   * yang memakai PTK ini — sebagai guru pengampu ATAU pembina
   * kegiatan. Mengembalikan baris jadwal penabrak atau null.
   */
  private function cariTabrakanPtk(int $ptkId, array $data, Rombel $rombel, int $semesterId)
  {
    return JadwalPelajaran::with('rombel')
      ->where('semester_id', $semesterId)
      ->where('hari', $data['hari'])
      ->where('jam_ke', $data['jam_ke'])
      ->where('rombel_id', '!=', $rombel->id)
      ->where(function ($q) use ($ptkId) {
        $q->where('pembina_id', $ptkId)
          ->orWhereHas('pengampu', fn($q2) => $q2->where('ptk_id', $ptkId));
      })
      ->first();
  }

  public function hapusSel(Request $request)
  {
    $data = $request->validate([
      'rombel_id' => 'required|integer',
      'hari' => 'required|integer|min:1|max:6',
      'jam_ke' => 'required|integer|min:1|max:12',
    ]);

    $sekolahId = $this->sekolahKonteksIdNullable();
    $semesterId = $this->semesterId();

    JadwalPelajaran::where('rombel_id', $data['rombel_id'])
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->where('hari', $data['hari'])
      ->where('jam_ke', $data['jam_ke'])
      ->delete();

    return response()->json(['ok' => true]);
  }

  // ── Pembantu ──────────────────────────────────────────────────

  /**
   * Sekolah konteks pengguna (pola yang sama dengan modul Kartu):
   * Admin memilih lewat ?sekolah_id= (sesi 'admin_sekolah_id'),
   * peran lain memakai sekolah PTK-nya.
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
    if (!$sekolahId) {
      abort(403, 'Akun Anda belum terhubung ke sekolah mana pun.');
    }

    return (int) $sekolahId;
  }

  private function semesterId(): int
  {
    $id = session('semester_id')
      ?? Semester::where('is_active', true)->value('id');

    return (int) $id;
  }

  /** Pastikan rombel milik sekolah konteks + semester sesi. */
  private function jagaRombel(Rombel $rombel): int
  {
    $sekolahId = $this->sekolahKonteksIdNullable();

    if (
      $rombel->sekolah_id !== $sekolahId
      || $rombel->semester_id !== $this->semesterId()
    ) {
      abort(404);
    }

    return $sekolahId;
  }

  /** Mapel yang berlaku untuk rombel ini (aturan tunggal mapping). */
  private function getMapels(Sekolah $sekolah, Rombel $rombel, int $semesterId)
  {
    return MapelMapping::with(['masterMapel.kelompok', 'kelompok'])
      ->berlakuUntuk($sekolah->id, $sekolah->jenjang, $rombel->tingkat, $semesterId, $rombel->kurikulum_id)
      ->get()
      ->sortBy(fn($m) => [
        $m->kelompokTampil()?->urutan ?? 999,
        $m->urutan ?? 999,
      ]);
  }

  /** Memo daftar model per sekolah+semester selama 1 request. */
  private array $memoModel = [];

  /** Semua model jadwal sekolah+semester, urut dibuat. */
  private function daftarModel(int $sekolahId, int $semesterId)
  {
    $kunci = $sekolahId . ':' . $semesterId;
    if (!isset($this->memoModel[$kunci])) {
      $this->memoModel[$kunci] = JadwalPengaturan::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)
        ->orderBy('id')
        ->get();
    }

    return $this->memoModel[$kunci];
  }

  /**
   * Model yang berlaku untuk satu rombel: model yang DIPILIH
   * rombel itu (tabel_rombel.jadwal_pengaturan_id) bila masih
   * ada; selain itu model pertama; null bila sekolah belum
   * punya model sama sekali. (Keputusan Mark: model dipilih
   * saat menyusun jadwal, bukan menurut tingkat.)
   */
  private function modelUntuk(int $sekolahId, int $semesterId, ?Rombel $rombel = null): ?JadwalPengaturan
  {
    $models = $this->daftarModel($sekolahId, $semesterId);
    if ($models->isEmpty()) {
      return null;
    }

    if ($rombel && $rombel->jadwal_pengaturan_id) {
      $pilihan = $models->firstWhere('id', (int) $rombel->jadwal_pengaturan_id);
      if ($pilihan) {
        return $pilihan;
      }
    }

    return $models->first();
  }

  /** Kerangka dari satu model; bawaan bila model/kerangka kosong. */
  private function kerangkaModel(?JadwalPengaturan $model, Sekolah $sekolah): array
  {
    if ($model && is_array($model->kerangka) && $model->kerangka) {
      $hari = [];
      foreach ($model->kerangka as $h => $frame) {
        $hari[(int) $h] = $frame;
      }

      return ['tersimpan' => true, 'hari' => $hari, 'model' => $model];
    }

    return [
      'tersimpan' => false,
      'hari' => $this->kerangkaBawaan($sekolah),
      'model' => $model,
    ];
  }

  /**
   * Kerangka jam yang berlaku: model untuk tingkat rombel
   * (bila tingkat diberikan), selain itu model umum/pertama;
   * pola bawaan bila belum ada model. Bentuk: ['tersimpan',
   * 'hari' => [...], 'model' => ?JadwalPengaturan].
   */
  private function kerangka(Sekolah $sekolah, int $semesterId, ?Rombel $rombel = null): array
  {
    return $this->kerangkaModel(
      $this->modelUntuk($sekolah->id, $semesterId, $rombel),
      $sekolah
    );
  }

  /** Ambil model terikat konteks sekolah + semester sesi. */
  private function cariModel($id): JadwalPengaturan
  {
    return JadwalPengaturan::where('id', $id)
      ->where('sekolah_id', $this->sekolahKonteksIdNullable())
      ->where('semester_id', $this->semesterId())
      ->firstOrFail();
  }

  /**
   * Pola bawaan (hanya tebakan awal sebelum Operator mengatur):
   * hari sekolah mengikuti libur mingguan Profil Sekolah; Senin–
   * Kamis 8 jam, Jumat 6, Sabtu 7; mulai 07.00, 35 menit per jam,
   * istirahat sesudah jam ke-3 dan ke-6 (15 menit).
   */
  private function kerangkaBawaan(Sekolah $sekolah): array
  {
    $libur = $sekolah->detailTerbaru?->libur_mingguan;
    if (!is_array($libur) || $libur === []) {
      $libur = ['Minggu'];
    }

    $jumlahBawaan = [1 => 8, 2 => 8, 3 => 8, 4 => 8, 5 => 6, 6 => 7];
    $kerangka = [];

    foreach (self::HARI as $hari => $nama) {
      if (in_array($nama, $libur, true)) {
        continue;
      }
      $jumlah = $jumlahBawaan[$hari];
      $istirahat = [];
      foreach ([3, 6] as $setelah) {
        if ($setelah < $jumlah) {
          $istirahat[] = ['setelah' => $setelah, 'menit' => 15];
        }
      }
      $kerangka[$hari] = [
        'jumlah_jam' => $jumlah,
        'mulai' => '07:00',
        'menit' => 35,
        'istirahat' => $istirahat,
      ];
    }

    return $kerangka;
  }

  /**
   * Garis waktu satu hari dari kerangkanya. Semua kejadian hari
   * itu — pelajaran, istirahat, dan kegiatan tetap — diurutkan
   * pada satu jam berjalan: setiap kejadian non-pelajaran
   * menempati durasinya sendiri lalu menggeser pelajaran
   * sesudahnya. Jangkar (anchor) = jumlah pelajaran yang sudah
   * selesai sebelum kejadian dimulai; istirahat "sesudah jam N"
   * berjangkar N, kegiatan "sebelum jam N" berjangkar N-1 dan
   * "sesudah jam N" berjangkar N.
   *
   * Hasil: ['pelajaran' => [jamKe => [mulai, selesai]],
   *         'lainnya'   => [anchor => [['nama','jenis','menit','waktu']]]]
   */
  private function garisWaktu(array $frame): array
  {
    [$jh, $jm] = array_map('intval', explode(':', $frame['mulai'] ?? '07:00'));
    $t = $jh * 60 + $jm;
    $menit = (int) ($frame['menit'] ?? 35);
    $jumlah = (int) ($frame['jumlah_jam'] ?? 0);
    $fmt = fn(int $m) => sprintf('%02d.%02d', intdiv($m, 60), $m % 60);

    $kejadian = [];
    foreach ($frame['istirahat'] ?? [] as $jeda) {
      $kejadian[(int) $jeda['setelah']][] = [
        'jenis' => 'istirahat',
        'nama' => 'Istirahat',
        'menit' => (int) $jeda['menit'],
      ];
    }
    foreach ($frame['kegiatan'] ?? [] as $keg) {
      $anchor = ($keg['posisi'] ?? 'sebelum') === 'sesudah'
        ? (int) $keg['jam']
        : (int) $keg['jam'] - 1;
      $kejadian[$anchor][] = [
        'jenis' => 'kegiatan',
        'nama' => $keg['nama'],
        'menit' => (int) $keg['menit'],
      ];
    }

    $pelajaran = [];
    $lainnya = [];
    for ($anchor = 0; $anchor <= $jumlah; $anchor++) {
      foreach ($kejadian[$anchor] ?? [] as $ev) {
        $lainnya[$anchor][] = [
          'nama' => $ev['nama'],
          'jenis' => $ev['jenis'],
          'menit' => $ev['menit'],
          'waktu' => [$fmt($t), $fmt($t + $ev['menit'])],
        ];
        $t += $ev['menit'];
      }
      if ($anchor < $jumlah) {
        $jam = $anchor + 1;
        $pelajaran[$jam] = [$fmt($t), $fmt($t + $menit)];
        $t += $menit;
      }
    }

    return ['pelajaran' => $pelajaran, 'lainnya' => $lainnya];
  }
}
