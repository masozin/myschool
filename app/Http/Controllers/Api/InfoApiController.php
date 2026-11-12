<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\InfoHariIni;
use Illuminate\Http\Request;

/**
 * API info hari ini untuk Beranda aplikasi mobile: kegiatan
 * Kalender Pendidikan hari ini + PTK yang berulang tahun.
 * Aturannya memakai App\Support\InfoHariIni — sama persis
 * dengan kartu di dashboard web.
 */
class InfoApiController extends Controller
{
  public function hariIni(Request $request)
  {
    $user = $request->user();
    $ptkSaya = $user->ptk;
    $sekolahId = $ptkSaya?->sekolah_id;
    $hariIni = now();

    $kaldik = InfoHariIni::kaldikPada($hariIni)->map(fn($k) => [
      'kegiatan' => $k->kegiatan,
      'jenis' => $k->jenis,
      'is_libur' => (bool) $k->is_libur,
      'tanggal_mulai' => $k->tanggal_mulai?->format('Y-m-d'),
      'tanggal_selesai' => $k->tanggal_selesai?->format('Y-m-d'),
    ])->values();

    $ulangTahun = InfoHariIni::ulangTahunPada($hariIni, $sekolahId)
      ->map(function ($p) use ($hariIni, $ptkSaya) {
        $saya = $ptkSaya && (int) $ptkSaya->id === (int) $p->id;
        $ucapan = $saya ? InfoHariIni::ucapanUntuk($p, $hariIni) : null;
        return [
          'id' => $p->id,
          'nama' => $p->nama_lengkap,
          'jabatan' => $p->jabatan,
          'usia' => InfoHariIni::usiaPada($p, $hariIni),
          'saya' => $saya,
          'ucapan_judul' => $ucapan['judul'] ?? null,
          'ucapan_doa' => $ucapan['doa'] ?? null,
        ];
      })->values();

    return response()->json([
      'tanggal' => $hariIni->format('Y-m-d'),
      'kaldik' => $kaldik,
      'ulang_tahun' => $ulangTahun,
    ]);
  }
}
