<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Skl;
use Illuminate\Http\Request;

class ValidasiKelulusan extends Controller
{
  // ── Halaman publik pencarian ──────────────────────────────────────────────
  public function index()
  {
    return view('publik.skhu.cari', [
      'pageConfigs' => ['layout' => 'front'],
    ]);
  }

  // ── API: cari SKHU berdasarkan NISN ───────────────────────────────────────
  // GET /api/skhu/{nisn}
  public function validasiSkhu(string $nisn)
  {
    // Validasi format NISN: 10 digit angka
    if (!preg_match('/^\d{10}$/', $nisn)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Format NISN tidak valid. NISN harus terdiri dari 10 digit angka.',
      ], 422);
    }

    $siswa = Siswa::where('nisn', $nisn)->first();

    if (!$siswa) {
      return response()->json([
        'status'  => 'not_found',
        'message' => 'Data siswa dengan NISN tersebut tidak ditemukan.',
      ], 404);
    }

    // Ambil semua SKHU final siswa ini (semua jenjang)
    $skhuList = $siswa->skhu()
      ->with([
        'sekolah.detailTerbaru',
        'semester',
        'nilais',
      ])
      ->where('status', 'final')
      ->orderByDesc('tanggal_terbit')
      ->get();

    if ($skhuList->isEmpty()) {
      return response()->json([
        'status'  => 'not_found',
        'message' => 'Belum ada data SKHU final untuk NISN tersebut.',
      ], 404);
    }

    // Format response
    $data = $skhuList->map(function ($skhu) {
      return [
        'id'                   => $skhu->id,
        'nomor_surat'          => $skhu->nomor_surat,
        'nomor_ujian'          => $skhu->nomor_ujian,
        'tanggal_mulai_ujian'  => $skhu->tanggal_mulai_ujian?->translatedFormat('d F Y'),
        'tanggal_selesai_ujian' => $skhu->tanggal_selesai_ujian?->translatedFormat('d F Y'),
        'tanggal_terbit'       => $skhu->tanggal_terbit?->translatedFormat('d F Y'),
        'tahun_pelajaran'      => $skhu->semester->tahun_pelajaran ?? '-',
        'sekolah'              => [
          'nama'         => $skhu->sekolah?->detailTerbaru?->nama_sekolah ?? '-',
          'npsn'         => $skhu->sekolah?->npsn ?? '-',
          'jenjang'      => $skhu->sekolah?->jenjang ?? '-',
          'kepala_sekolah' => $skhu->sekolah?->detailTerbaru->kepala_sekolah ?? '-',
        ],
        'nilai_rata_rata'      => $skhu->nilaiRataRata(),
        'nilai_per_kelompok'   => $this->formatNilai($skhu),
      ];
    });

    return response()->json([
      'status' => 'ok',
      'siswa'  => [
        'nama'        => $siswa->nama_siswa,
        'nisn'        => $siswa->nisn,
        'tempat_lahir' => $siswa->tempat_lahir,
        'tanggal_lahir' => $siswa->tgl_lahir
          ? \Carbon\Carbon::parse($siswa->tgl_lahir)->translatedFormat('d F Y')
          : null,
        'nama_ayah'   => $siswa->nama_ayah,
      ],
      'skhu_list' => $data,
    ]);
  }

  // ── Helper: format nilai per kelompok ────────────────────────────────────
  private function formatNilai($skhu): array
  {
    $result = [];

    // 1. Urutkan nilai berdasarkan 'urutan' dari MapelMapping
    $nilaiUrut = $skhu->nilais->sortBy(fn($n) => $n->mapelMapping?->urutan ?? 999);

    foreach ($nilaiUrut as $nilai) {
      // 2. Ambil kelompok mapel dari: mapelMapping -> masterMapel -> kelompok
      $namaKelompok = $nilai->mapelMapping?->kelompok?->nama
        ?? $nilai->mapelMapping?->masterMapel?->kelompok?->nama
        ?? 'Lainnya';

      $result[$namaKelompok][] = [
        'nama_mapel'     => $nilai->mapelMapping?->masterMapel?->nama ?? '-',
        'nama_mapel_sub' => $nilai->mapelMapping?->masterMapel?->nama_sub_mapel,
        'nilai'          => number_format($nilai->nilai, 2, '.', ''),
      ];
    }

    // 3. Konversi ke array terindeks untuk JSON
    $formatted = [];
    foreach ($result as $kelompok => $items) {
      $formatted[] = [
        'kelompok' => $kelompok,
        'mapels'   => $items,
      ];
    }

    return $formatted;
  }

  public function validasiSkl(string $nisn)
  {
    if (!preg_match('/^\d{10}$/', $nisn)) {
      return response()->json([
        'status'  => 'error',
        'message' => 'Format NISN tidak valid. NISN harus terdiri dari 10 digit angka.',
      ], 422);
    }

    $siswa = Siswa::where('nisn', $nisn)->first();

    if (!$siswa) {
      return response()->json([
        'status'  => 'not_found',
        'message' => 'Data siswa dengan NISN tersebut tidak ditemukan.',
      ], 404);
    }

    // Hanya SKL yang statusnya 'disetujui'
    $sklList = Skl::with([
      'sekolah.detailTerbaru',
      'semester',
      'nilais.mapelMapping.masterMapel.kelompok', 'nilais.mapelMapping.kelompok',
    ])
      ->whereHas('siswa', fn($q) => $q->where('nisn', $nisn))
      ->where('status', 'disetujui')
      ->orderByDesc('tanggal_terbit')
      ->get();

    if ($sklList->isEmpty()) {
      return response()->json([
        'status'  => 'not_found',
        'message' => 'Belum ada data SKL yang disetujui untuk NISN tersebut.',
      ], 404);
    }

    // ── PENTING: key harus 'skl_list' agar cocok dengan frontend ───────────
    $data = $sklList->map(function ($skl) {
      return [
        'id'              => $skl->id,
        'nomor_skl'       => $skl->nomor_skl,
        'tanggal_terbit'  => $skl->tanggal_terbit?->translatedFormat('d F Y'),
        'tahun_pelajaran' => $skl->semester->tahun_pelajaran ?? '-',
        'sekolah'         => [
          'nama'           => $skl->sekolah?->detailTerbaru?->nama_sekolah ?? '-',
          'npsn'           => $skl->sekolah?->npsn ?? '-',
          'jenjang'        => $skl->sekolah?->jenjang ?? '-',
          'kepala_sekolah' => $skl->sekolah?->detailTerbaru?->kepala_sekolah ?? '-',
        ],
        'nilai_rata_rata'    => $skl->nilaiRataRata(),
        'nilai_per_kelompok' => $this->formatNilaiSkl($skl),
      ];
    });

    return response()->json([
      'status' => 'ok',
      'siswa'  => [
        'nama'          => $siswa->nama_siswa,
        'nisn'          => $siswa->nisn,
        'tempat_lahir'  => $siswa->tempat_lahir,
        'tanggal_lahir' => $siswa->tgl_lahir
          ? \Carbon\Carbon::parse($siswa->tgl_lahir)->translatedFormat('d F Y')
          : null,
        'nama_ayah'     => $siswa->nama_ayah,
      ],
      'skl_list' => $data,
    ]);
  }

  // ── Helper: format nilai SKL per kelompok ──────────────────────────────────
  private function formatNilaiSkl($skl): array
  {
    $result = [];

    $nilaiUrut = $skl->nilais->sortBy(fn($n) => $n->mapelMapping?->urutan ?? 999);

    foreach ($nilaiUrut as $nilai) {
      $namaKelompok = $nilai->mapelMapping?->kelompok?->nama
        ?? $nilai->mapelMapping?->masterMapel?->kelompok?->nama
        ?? 'Lainnya';

      $result[$namaKelompok][] = [
        'nama_mapel'     => $nilai->mapelMapping?->masterMapel?->nama ?? '-',
        'nama_mapel_sub' => $nilai->mapelMapping?->masterMapel?->nama_sub_mapel,
        'nilai'          => $nilai->nilai_akhir !== null
          ? number_format($nilai->nilai_akhir, 2, '.', '')
          : null,
        'nilai_huruf'    => $nilai->nilai_huruf,
      ];
    }

    $formatted = [];
    foreach ($result as $kelompok => $items) {
      $formatted[] = [
        'kelompok' => $kelompok,
        'mapels'   => $items,
      ];
    }

    return $formatted;
  }
}
