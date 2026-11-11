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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        'totalKotak' => 0,
        'pengaturanAda' => false,
        'bolehSusun' => $bolehSusun,
      ]);
    }

    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $kerangka = $this->kerangka($sekolah, $semesterId);
    $totalKotak = array_sum(array_map(
      fn($f) => (int) ($f['jumlah_jam'] ?? 0),
      $kerangka['hari']
    ));

    $rombels = Rombel::with('ptk')
      ->where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->orderBy('tingkat')
      ->orderBy('nama_rombel')
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
      'totalKotak' => $totalKotak,
      'pengaturanAda' => $kerangka['tersimpan'],
      'bolehSusun' => $bolehSusun,
    ]);
  }

  // ── Pengaturan kerangka jam ───────────────────────────────────
  public function pengaturan()
  {
    $sekolahId = $this->sekolahKonteksIdNullable();
    $sekolah = Sekolah::with('detailTerbaru')->findOrFail($sekolahId);
    $semesterId = $this->semesterId();
    $kerangka = $this->kerangka($sekolah, $semesterId);

    return view('content.jadwal-pelajaran.pengaturan', [
      'sekolah' => $sekolah,
      'semester' => Semester::find($semesterId),
      'kerangka' => $kerangka['hari'],
      'tersimpan' => $kerangka['tersimpan'],
      'namaHari' => self::HARI,
    ]);
  }

  public function simpanPengaturan(Request $request)
  {
    $data = $request->validate([
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

    $sekolahId = $this->sekolahKonteksIdNullable();
    $semesterId = $this->semesterId();

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

    JadwalPengaturan::updateOrCreate(
      ['sekolah_id' => $sekolahId, 'semester_id' => $semesterId],
      ['kerangka' => $kerangka]
    );

    return redirect()
      ->route('jadwal-pelajaran.pengaturan')
      ->with('success', 'Kerangka jam tersimpan. Jadwal yang sudah terisi di luar kerangka baru akan ditandai di editor.');
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
    $kerangka = $this->kerangka($sekolah, $semesterId);
    $hariFrame = $kerangka['hari'];
    $hariAktif = array_keys($hariFrame);
    sort($hariAktif);
    $maxJam = $hariFrame
      ? max(array_map(fn($f) => (int) $f['jumlah_jam'], $hariFrame))
      : 0;

    // Garis waktu per hari dari kerangka: jam tampil pelajaran
    // plus posisi & jam tampil kegiatan tetap (Upacara, Senam,
    // ...) dan istirahat yang menggesernya.
    $waktu = [];
    $kegiatanWaktu = [];
    $jangkarKegiatan = [];
    foreach ($hariFrame as $hari => $frame) {
      $gw = $this->garisWaktu($frame);
      $waktu[$hari] = $gw['pelajaran'];
      foreach ($gw['lainnya'] as $anchor => $items) {
        foreach ($items as $it) {
          if ($it['jenis'] === 'kegiatan') {
            $kegiatanWaktu[$hari][$anchor][] = $it;
            $jangkarKegiatan[$anchor] = true;
          }
        }
      }
    }
    $jangkarKegiatan = array_keys($jangkarKegiatan);
    sort($jangkarKegiatan);

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
      'kegiatanWaktu' => $kegiatanWaktu,
      'jangkarKegiatan' => $jangkarKegiatan,
      'guruList' => $guruList,
      'pengampuList' => $pengampuList,
      'terplot' => $terplot,
      'luarKerangka' => $luarKerangka,
      'sibuk' => $sibuk,
      'tersimpan' => $kerangka['tersimpan'],
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
    $frame = $this->kerangka($sekolah, $semesterId)['hari'][$data['hari']] ?? null;
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

  /**
   * Kerangka jam sekolah+semester: yang tersimpan bila ada,
   * selain itu pola bawaan. Bentuk: ['tersimpan' => bool,
   * 'hari' => [hari => ['jumlah_jam','mulai','menit','istirahat']]].
   */
  private function kerangka(Sekolah $sekolah, int $semesterId): array
  {
    $tersimpan = JadwalPengaturan::where('sekolah_id', $sekolah->id)
      ->where('semester_id', $semesterId)
      ->first();

    if ($tersimpan && is_array($tersimpan->kerangka) && $tersimpan->kerangka) {
      $hari = [];
      foreach ($tersimpan->kerangka as $h => $frame) {
        $hari[(int) $h] = $frame;
      }
      return ['tersimpan' => true, 'hari' => $hari];
    }

    return ['tersimpan' => false, 'hari' => $this->kerangkaBawaan($sekolah)];
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
   *         'lainnya'   => [anchor => [['nama','jenis','waktu']]]]
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
