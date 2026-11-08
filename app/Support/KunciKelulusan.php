<?php

namespace App\Support;

use App\Models\Semester;
use App\Models\SkhuKonfigurasi;
use App\Models\SklKonfigurasi;

/**
 * Kunci otomatis dokumen kelulusan (SKHU & SKL).
 *
 * Sebuah angkatan (semester Genap tempat dokumen terbit)
 * terkunci begitu MUSIM kelulusan berikutnya dimulai — yaitu
 * ketika semester Genap dari tahun pelajaran yang lebih baru
 * menjadi semester aktif. Sepanjang semester Ganjil sesudah
 * kelulusan, angkatan lalu masih boleh dikoreksi dan dicetak
 * ulang (memang begitu alur kerjanya); melihat dan mencetak
 * tidak pernah dikunci, hanya mengubah/menghapus.
 *
 * Admin dapat membuka kunci satu angkatan bila diperlukan;
 * penandanya (kunci_dibuka) tersimpan pada konfigurasi
 * angkatan di masing-masing modul.
 */
class KunciKelulusan
{
  /** Semester Genap yang menjadi musim kelulusan saat ini. */
  public static function semesterMusimIni(): ?Semester
  {
    $aktif = Semester::where('is_active', true)->first();
    if (!$aktif) {
      return null;
    }

    if (strtolower((string) $aktif->semester) === 'genap') {
      return $aktif;
    }

    // Sesi Ganjil: musim kelulusannya adalah Genap tahun lalu
    $awal   = (int) substr((string) $aktif->tahun_pelajaran, 0, 4);
    $tpLalu = ($awal - 1) . '/' . $awal;

    return Semester::where('tahun_pelajaran', $tpLalu)
      ->where('semester', 'Genap')
      ->first();
  }

  /** Apakah angkatan ini SUDAH melewati musimnya (tanpa melihat buka manual). */
  public static function terkunciOtomatis(int $semesterId): bool
  {
    $target = Semester::find($semesterId);
    $musim  = static::semesterMusimIni();

    if (!$target || !$musim) {
      return false;
    }

    $kT = Semester::kunciKronologis($target);
    $kM = Semester::kunciKronologis($musim);

    return ($kM[0] * 10 + $kM[1]) > ($kT[0] * 10 + $kT[1]);
  }

  /** Apakah Admin sudah membuka kunci angkatan ini secara manual. */
  public static function dibukaManual(string $modul, int $sekolahId, int $semesterId): bool
  {
    $konfig = $modul === 'skhu'
      ? SkhuKonfigurasi::untukSekolah($sekolahId, $semesterId)
      : SklKonfigurasi::where('sekolah_id', $sekolahId)->where('semester_id', $semesterId)->first();

    return (bool) ($konfig?->kunci_dibuka ?? false);
  }

  /** Keadaan kunci yang berlaku: otomatis DAN tidak dibuka manual. */
  public static function terkunci(string $modul, int $sekolahId, int $semesterId): bool
  {
    return static::terkunciOtomatis($semesterId)
      && !static::dibukaManual($modul, $sekolahId, $semesterId);
  }
}
