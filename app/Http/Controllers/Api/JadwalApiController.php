<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use App\Models\JadwalPengaturan;
use App\Models\Ptk;
use App\Models\Rombel;
use App\Models\Sekolah;
use App\Models\Semester;
use Illuminate\Http\Request;

/**
 * API jadwal pelajaran untuk aplikasi mobile guru.
 *
 * Konteks: sekolah dari PTK pengguna (Admin boleh menyebut
 * sekolah_id), semester = semester AKTIF (aplikasi tidak punya
 * sesi semester seperti web). Kerangka jam mengikuti aturan
 * yang sama dengan web: model pilihan rombel, lalu model
 * pertama, lalu pola bawaan; garis waktu dihitung dari
 * kerangka model rombel masing-masing.
 *
 * Bentuk blok di semua endpoint:
 * {mulai: "07.00", selesai: "07.35", jenis: "pelajaran" |
 * "istirahat" | "kegiatan", label: string, sub: string}
 */
class JadwalApiController extends Controller
{
  private const HARI = [
    1 => 'Senin',
    2 => 'Selasa',
    3 => 'Rabu',
    4 => 'Kamis',
    5 => 'Jumat',
    6 => 'Sabtu',
  ];

  private function konteks(Request $request): array
  {
    $user = $request->user();
    $ptk = $user->ptk;

    $sekolahId = $ptk?->sekolah_id;
    if (!$sekolahId && $user->hasRole('Admin') && $request->filled('sekolah_id')) {
      $sekolahId = (int) $request->sekolah_id;
    }

    $sekolah = $sekolahId
      ? Sekolah::with('detailTerbaru')->find($sekolahId)
      : null;

    $semester = $request->filled('semester_id')
      ? Semester::find($request->semester_id)
      : Semester::where('is_active', true)->first();

    return [
      'user' => $user,
      'ptk' => $ptk,
      'sekolah' => $sekolah,
      'semester' => $semester,
    ];
  }

  private function ringkasanSemester(?Semester $semester): ?array
  {
    if (!$semester) {
      return null;
    }
    return [
      'id' => $semester->id,
      'tahun_pelajaran' => $semester->tahun_pelajaran,
      'semester' => $semester->semester,
    ];
  }

  // ── Kerangka & garis waktu (menyelaraskan web) ─────────────

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
        'kegiatan' => [],
      ];
    }

    return $kerangka;
  }

  private function kerangkaRombel(Sekolah $sekolah, int $semesterId, Rombel $rombel): array
  {
    $models = JadwalPengaturan::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semesterId)
      ->orderBy('id')
      ->get();

    $model = null;
    if ($rombel->jadwal_pengaturan_id) {
      $model = $models->firstWhere('id', (int) $rombel->jadwal_pengaturan_id);
    }
    $model ??= $models->first();

    if ($model && is_array($model->kerangka) && $model->kerangka !== []) {
      return $model->kerangka;
    }
    return $this->kerangkaBawaan($sekolah);
  }

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

  private function keMenit(string $w): int
  {
    [$j, $m] = array_map('intval', explode('.', str_replace(':', '.', $w)));
    return $j * 60 + $m;
  }

  // ── Susunan blok ───────────────────────────────────────────

  /** Semua blok satu rombel pada satu hari (untuk jadwal kelas). */
  private function blokRombelHari(Sekolah $sekolah, int $semesterId, Rombel $rombel, int $hari): array
  {
    $frame = $this->kerangkaRombel($sekolah, $semesterId, $rombel)[$hari] ?? null;
    if (!$frame) {
      return [];
    }
    $gw = $this->garisWaktu($frame);

    $sel = JadwalPelajaran::with(['pengampu.mapping.masterMapel', 'pengampu.ptk', 'pembina'])
      ->where('rombel_id', $rombel->id)
      ->where('semester_id', $semesterId)
      ->where('hari', $hari)
      ->get()
      ->keyBy('jam_ke');

    $blok = [];
    foreach ($gw['pelajaran'] as $jam => $w) {
      $c = $sel->get($jam);
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
    return $blok;
  }

  /** Blok mengajar seorang guru pada satu hari (lintas rombel). */
  private function blokGuruHari(Sekolah $sekolah, int $semesterId, int $ptkId, int $hari): array
  {
    $baris = JadwalPelajaran::with(['rombel', 'pengampu.mapping.masterMapel', 'pembina'])
      ->where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semesterId)
      ->where('hari', $hari)
      ->where(function ($q) use ($ptkId) {
        $q->whereHas('pengampu', fn($qq) => $qq->where('ptk_id', $ptkId))
          ->orWhere('pembina_id', $ptkId);
      })
      ->get();

    $blok = [];
    $gwCache = [];
    foreach ($baris as $b) {
      if (!$b->rombel) {
        continue;
      }
      if (!isset($gwCache[$b->rombel_id])) {
        $frame = $this->kerangkaRombel($sekolah, $semesterId, $b->rombel)[$hari] ?? null;
        $gwCache[$b->rombel_id] = $frame ? $this->garisWaktu($frame) : null;
      }
      $w = $gwCache[$b->rombel_id]['pelajaran'][$b->jam_ke] ?? null;
      if (!$w) {
        continue;
      }
      $blok[] = [
        'mulai' => $w[0],
        'selesai' => $w[1],
        'jenis' => 'pelajaran',
        'label' => $b->rombel->nama_rombel,
        'sub' => $b->pengampu
          ? ($b->pengampu->mapping?->masterMapel?->nama ?? '-')
          : ('Kegiatan: ' . ($b->nama_kegiatan ?? '-')),
      ];
    }
    usort($blok, fn($a, $b) => strcmp($a['mulai'], $b['mulai']));
    return $blok;
  }

  // ── Endpoint ───────────────────────────────────────────────

  /** Jadwal hari ini untuk guru: sedang, berikutnya, daftar, kelas wali. */
  public function hariIni(Request $request)
  {
    ['ptk' => $ptk, 'sekolah' => $sekolah, 'semester' => $semester] = $this->konteks($request);

    $sekarang = now();
    $hariIni = (int) $sekarang->dayOfWeekIso; // 1=Senin .. 7=Minggu
    $jamMenit = $sekarang->hour * 60 + $sekarang->minute;
    $bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    $hasil = [
      'semester' => $this->ringkasanSemester($semester),
      'hari' => [
        'nomor' => $hariIni,
        'nama' => self::HARI[$hariIni] ?? 'Minggu',
        'tanggal' => $sekarang->format('d') . ' ' . $bulan[(int) $sekarang->format('n')] . ' ' . $sekarang->format('Y'),
        'jam' => $sekarang->format('H.i'),
      ],
      'sedang' => null,
      'berikutnya' => null,
      'daftar' => [],
      'kelas_sekarang' => null,
    ];

    if (!$ptk || !$sekolah || !$semester) {
      return response()->json($hasil);
    }

    $daftar = $this->blokGuruHari($sekolah, $semester->id, (int) $ptk->id, $hariIni);
    $hasil['daftar'] = $daftar;
    foreach ($daftar as $d) {
      if ($this->keMenit($d['mulai']) <= $jamMenit && $jamMenit < $this->keMenit($d['selesai'])) {
        $hasil['sedang'] = $d;
      }
      if ($hasil['berikutnya'] === null && $this->keMenit($d['mulai']) > $jamMenit) {
        $hasil['berikutnya'] = $d;
      }
    }

    // Wali kelas: apa yang sedang berlangsung di kelasnya.
    $rombelWali = Rombel::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id)
      ->where('ptk_id', $ptk->id)
      ->first();
    if ($rombelWali) {
      foreach ($this->blokRombelHari($sekolah, $semester->id, $rombelWali, $hariIni) as $b) {
        if ($this->keMenit($b['mulai']) <= $jamMenit && $jamMenit < $this->keMenit($b['selesai'])) {
          $hasil['kelas_sekarang'] = [
            'rombel' => $rombelWali->nama_rombel,
            'mulai' => $b['mulai'],
            'selesai' => $b['selesai'],
            'label' => $b['label'] === '' ? 'Belum diisi' : $b['label'],
            'sub' => $b['sub'],
          ];
          break;
        }
      }
    }

    return response()->json($hasil);
  }

  /** Seluruh jadwal mengajar guru selama seminggu (per hari). */
  public function jadwalSaya(Request $request)
  {
    ['ptk' => $ptk, 'sekolah' => $sekolah, 'semester' => $semester] = $this->konteks($request);

    $hari = [];
    if ($ptk && $sekolah && $semester) {
      foreach (self::HARI as $nomor => $nama) {
        $hari[] = [
          'nomor' => $nomor,
          'nama' => $nama,
          'blok' => $this->blokGuruHari($sekolah, $semester->id, (int) $ptk->id, $nomor),
        ];
      }
    }

    return response()->json([
      'semester' => $this->ringkasanSemester($semester),
      'hari' => $hari,
    ]);
  }

  /** Daftar rombel sekolah pengguna pada semester aktif. */
  public function daftarRombel(Request $request)
  {
    ['sekolah' => $sekolah, 'semester' => $semester] = $this->konteks($request);

    $daftar = [];
    if ($sekolah && $semester) {
      $daftar = Rombel::with('ptk')
        ->where('sekolah_id', $sekolah->id)
        ->where('semester_id', $semester->id)
        ->orderBy('tingkat')
        ->orderBy('nama_rombel')
        ->get()
        ->map(fn($r) => [
          'id' => $r->id,
          'nama_rombel' => $r->nama_rombel,
          'tingkat' => $r->tingkat,
          'wali_kelas' => $r->ptk?->nama_lengkap,
        ])
        ->all();
    }

    return response()->json([
      'semester' => $this->ringkasanSemester($semester),
      'rombel' => $daftar,
    ]);
  }

  /** Jadwal lengkap satu rombel selama seminggu (per hari). */
  public function jadwalRombel(Request $request, $id)
  {
    ['sekolah' => $sekolah, 'semester' => $semester] = $this->konteks($request);

    if (!$sekolah || !$semester) {
      return response()->json(['message' => 'Konteks sekolah / semester tidak ditemukan.'], 404);
    }

    $rombel = Rombel::with('ptk')
      ->where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semester->id)
      ->find($id);
    if (!$rombel) {
      return response()->json(['message' => 'Rombel tidak ditemukan.'], 404);
    }

    $hari = [];
    foreach (self::HARI as $nomor => $nama) {
      $blok = $this->blokRombelHari($sekolah, $semester->id, $rombel, $nomor);
      if ($blok !== []) {
        $hari[] = ['nomor' => $nomor, 'nama' => $nama, 'blok' => $blok];
      }
    }

    return response()->json([
      'semester' => $this->ringkasanSemester($semester),
      'rombel' => [
        'id' => $rombel->id,
        'nama_rombel' => $rombel->nama_rombel,
        'tingkat' => $rombel->tingkat,
        'wali_kelas' => $rombel->ptk?->nama_lengkap,
      ],
      'hari' => $hari,
    ]);
  }
}
