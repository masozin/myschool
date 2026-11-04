<?php

namespace App\Exports;

use App\Models\Siswa;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SiswaExport extends DefaultValueBinder implements
  FromCollection,
  WithHeadings,
  WithMapping,
  WithColumnWidths,
  WithColumnFormatting,
  WithStyles,
  WithCustomValueBinder
{
  protected $sekolahId;
  protected $rombelId;
  protected $semesterId;
  protected $nomor = 0;

  // Kolom yang HARUS selalu dianggap teks (bukan angka/tanggal)
  protected $kolomText = ['C', 'D', 'N']; // NISN, NIK, NIS

  public function __construct($sekolahId = null, $rombelId = null, $semesterId = null)
  {
    $this->sekolahId  = $sekolahId;
    $this->rombelId   = $rombelId;
    $this->semesterId = $semesterId;
  }

  public function bindValue(Cell $cell, $value)
  {
    $col = preg_replace('/[0-9]/', '', $cell->getCoordinate());

    if (in_array($col, $this->kolomText) && $value !== null) {
      $cell->setValueExplicit((string) $value, DataType::TYPE_STRING);
      return true;
    }

    // String berawalan "=" (rumus, misal DATEDIF) otomatis dikenali
    // sebagai formula oleh parent::bindValue().
    return parent::bindValue($cell, $value);
  }

  public function collection()
  {
    $query = Siswa::with([
      'siswaRegistrasi' => function ($q) {
        $q->latest()->with('sekolah.detailTerbaru');
      },
      'rombelAnggota' => function ($q) {
        $q->where('semester_id', $this->semesterId)
          ->whereNull('end_date')
          ->with('rombel');
      }
    ])->whereHas('siswaRegistrasi');

    if ($this->sekolahId) {
      $query->whereHas('siswaRegistrasi', function ($q) {
        $q->where('sekolah_id', $this->sekolahId);
      });
    }

    if ($this->rombelId === 'Tanpa Rombel') {
      $query->whereDoesntHave('rombelAnggota', function ($q) {
        $q->where('semester_id', $this->semesterId)->whereNull('end_date');
      });
    } elseif ($this->rombelId) {
      $query->whereHas('rombelAnggota', function ($q) {
        $q->where('rombel_id', $this->rombelId)->whereNull('end_date');
      });
    }

    return $query->get();
  }

  public function headings(): array
  {
    return [
      'No',
      'Nama Siswa',
      'NISN',
      'NIK',
      'L/P',
      'Tempat Lahir',
      'Tanggal Lahir',
      'Usia',
      'Alamat',
      'Nama Ayah',
      'Nama Ibu',
      'Sekolah',
      'Rombel',
      'NIS',
      'Tingkat',
      'Tanggal Diterima',
      'Status',
    ];
  }

  public function map($siswa): array
  {
    $this->nomor++;
    $excelRow = $this->nomor + 1; // +1 karena baris 1 = header

    $registrasi = $siswa->siswaRegistrasi->first();
    $rombel     = $siswa->rombelAnggota->first()?->rombel;

    return [
      $this->nomor,
      $siswa->nama_siswa,
      (string) $siswa->nisn,
      (string) $siswa->nik,
      $siswa->jenis_kelamin,
      $siswa->tempat_lahir,
      $this->toExcelDate($siswa->tgl_lahir),                       // kolom G: tanggal asli
      $siswa->tgl_lahir ? "=DATEDIF(G{$excelRow},TODAY(),\"Y\") &\" th \"& DATEDIF(G{$excelRow},TODAY(),\"YM\") &\" bln \"" : null, // kolom H: rumus usia
      $siswa->alamat,
      $siswa->nama_ayah,
      $siswa->nama_ibu,
      $registrasi?->sekolah?->detailTerbaru?->nama_sekolah,
      $rombel?->nama_rombel ?? 'Tanpa Rombel',
      $registrasi ? (string) $registrasi->nis : null,
      $registrasi?->tingkat,
      $this->toExcelDate($registrasi?->tanggal_diterima),          // kolom P: tanggal asli
      $registrasi?->status,
    ];
  }

  /**
   * Konversi tanggal (string/Carbon) menjadi serial tanggal Excel,
   * supaya bisa dipakai rumus (DATEDIF, dsb) dan tetap bisa diurutkan/difilter
   * sebagai tanggal asli, bukan teks.
   */
  private function toExcelDate($value)
  {
    if (!$value) return null;

    $carbon = $value instanceof Carbon ? $value : Carbon::parse($value);
    return ExcelDate::PHPToExcel($carbon);
  }

  public function columnWidths(): array
  {
    return [
      'A' => 6,
      'B' => 30,
      'C' => 16,
      'D' => 18,
      'E' => 8,
      'F' => 20,
      'G' => 16,
      'H' => 16,
      'I' => 30,
      'J' => 25,
      'K' => 25,
      'L' => 25,
      'M' => 14,
      'N' => 10,
      'O' => 8,
      'P' => 16,
      'Q' => 12,
    ];
  }

  public function columnFormats(): array
  {
    return [
      'C' => NumberFormat::FORMAT_TEXT,        // NISN
      'D' => NumberFormat::FORMAT_TEXT,        // NIK
      'G' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Tanggal Lahir
      'N' => NumberFormat::FORMAT_TEXT,        // NIS
      'P' => NumberFormat::FORMAT_DATE_YYYYMMDD, // Tanggal Diterima
    ];
  }

  public function styles(Worksheet $sheet)
  {
    return [1 => ['font' => ['bold' => true]]];
  }
}
