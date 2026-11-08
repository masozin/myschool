<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Pembungkus workbook untuk impor Kalender Pendidikan.
 *
 * Tanpa pembatas ini, Maatwebsite menjalankan impor yang sama
 * ke SEMUA lembar di file — termasuk lembar 'Petunjuk' pada
 * template resmi, yang baris-baris teks arahannya ikut terbaca
 * sebagai data dan dilaporkan sebagai "tanggal mulai tidak
 * terbaca". Dengan pembungkus ini impor hanya membaca lembar
 * pertama (lembar data), apa pun lembar lain yang menyertainya.
 */
class KalenderPendidikanWorkbook implements WithMultipleSheets
{
  public function __construct(private KalenderPendidikanImport $lembar) {}

  public function sheets(): array
  {
    return [0 => $this->lembar];
  }
}
