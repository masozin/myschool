<?php

namespace App\Support;

use App\Models\KalenderPendidikan;
use App\Models\Ptk;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Info hari ini untuk dashboard web & aplikasi mobile:
 * kegiatan Kalender Pendidikan yang mencakup hari ini dan
 * PTK yang berulang tahun hari ini. Satu aturan untuk semua
 * tampilan, supaya web dan mobile tidak pernah berbeda cerita.
 */
class InfoHariIni
{
  /** Semua kegiatan kalender pendidikan yang mencakup tanggal ini. */
  public static function kaldikPada(Carbon $tanggal): Collection
  {
    return KalenderPendidikan::whereDate('tanggal_mulai', '<=', $tanggal)
      ->whereDate('tanggal_selesai', '>=', $tanggal)
      ->orderBy('tanggal_mulai')
      ->get();
  }

  /**
   * PTK aktif yang berulang tahun pada tanggal ini (bulan &
   * hari sama). $sekolahId null = semua sekolah (untuk Admin).
   */
  public static function ulangTahunPada(Carbon $tanggal, ?int $sekolahId): Collection
  {
    return Ptk::whereNotNull('tanggal_lahir')
      ->where('status', 'aktif')
      ->when($sekolahId, fn($q) => $q->where('sekolah_id', $sekolahId))
      ->whereMonth('tanggal_lahir', $tanggal->month)
      ->whereDay('tanggal_lahir', $tanggal->day)
      ->orderBy('nama_ptk')
      ->get();
  }

  /** Usia yang dicapai pada ulang tahun di tanggal ini. */
  public static function usiaPada(Ptk $ptk, Carbon $tanggal): ?int
  {
    if (!$ptk->tanggal_lahir) {
      return null;
    }
    return $tanggal->year - $ptk->tanggal_lahir->year;
  }

  /** Ucapan + doa ulang tahun (dipakai web & mobile apa adanya). */
  public static function ucapanUntuk(Ptk $ptk, Carbon $tanggal): array
  {
    $usia = self::usiaPada($ptk, $tanggal);
    $labelUsia = $usia ? " yang ke-{$usia}" : '';

    return [
      'judul' => "Selamat Ulang Tahun{$labelUsia}, {$ptk->nama_lengkap}!",
      'doa' => 'Di hari istimewa ini, kami mengucapkan selamat ulang tahun. '
        . 'Semoga panjang umur, senantiasa diberi kesehatan, dilancarkan rezekinya, '
        . 'dan selalu dalam lindungan Tuhan Yang Maha Esa. Semoga setiap langkah '
        . 'pengabdian Bapak/Ibu dalam mendidik anak-anak bangsa menjadi amal '
        . 'kebaikan yang tak terputus, dan tahun ini membawa lebih banyak '
        . 'kebahagiaan serta keberkahan.',
    ];
  }
}
