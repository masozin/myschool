<?php

namespace App\Support;

/**
 * Terbilang Bahasa Indonesia untuk nominal uang (dipakai
 * kwitansi SPJ Kelola BOS). Ejaan baku: "Seratus" dan
 * "Seribu" sebagai pengecualian, selebihnya dirangkai dari
 * satuan, belasan, puluhan, ratusan, dan kelompok ribuan
 * (Ribu, Juta, Miliar, Triliun).
 */
class Terbilang
{
  private const SATUAN = [
    '', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam',
    'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas',
  ];

  /** Nominal menjadi teks terbilang + "Rupiah", mis. "Delapan Puluh Satu Ribu Rupiah". */
  public static function rupiah(float|int $angka): string
  {
    $nilai = (int) round((float) $angka);

    if ($nilai === 0) {
      return 'Nol Rupiah';
    }

    $teks = self::eja(abs($nilai));

    return ($nilai < 0 ? 'Minus ' : '') . $teks . ' Rupiah';
  }

  private static function eja(int $n): string
  {
    if ($n < 12) {
      return self::SATUAN[$n];
    }
    if ($n < 20) {
      return self::eja($n - 10) . ' Belas';
    }
    if ($n < 100) {
      return trim(self::eja(intdiv($n, 10)) . ' Puluh ' . self::eja($n % 10));
    }
    if ($n < 200) {
      return trim('Seratus ' . self::eja($n - 100));
    }
    if ($n < 1000) {
      return trim(self::eja(intdiv($n, 100)) . ' Ratus ' . self::eja($n % 100));
    }
    if ($n < 2000) {
      return trim('Seribu ' . self::eja($n - 1000));
    }
    if ($n < 1_000_000) {
      return trim(self::eja(intdiv($n, 1_000)) . ' Ribu ' . self::eja($n % 1_000));
    }
    if ($n < 1_000_000_000) {
      return trim(self::eja(intdiv($n, 1_000_000)) . ' Juta ' . self::eja($n % 1_000_000));
    }
    if ($n < 1_000_000_000_000) {
      return trim(self::eja(intdiv($n, 1_000_000_000)) . ' Miliar ' . self::eja($n % 1_000_000_000));
    }

    return trim(self::eja(intdiv($n, 1_000_000_000_000)) . ' Triliun ' . self::eja($n % 1_000_000_000_000));
  }
}
