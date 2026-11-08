<?php

namespace App\Imports;

use App\Http\Controllers\KalenderPendidikanController;
use App\Models\KalenderPendidikan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Impor Kalender Pendidikan dari Excel.
 *
 * Kolom file: Tanggal Mulai | Tanggal Selesai | Jenis | Libur | Kegiatan
 * Baris pertama dianggap tajuk bila sel pertamanya tidak terbaca
 * sebagai tanggal. Tahun pelajaran BUKAN dari file — diambil dari
 * filter yang sedang aktif di halaman (sama seperti tambah manual),
 * dan semua tanggal dikunci ke rentangnya (1 Juli – 30 Juni).
 *
 * Baris yang tidak valid tidak menghentikan impor: baris valid
 * tetap masuk, sisanya dilaporkan per nomor baris. Baris yang
 * sama persis dengan data yang sudah ada (atau muncul dua kali
 * di file) dilewati dan dihitung.
 */
class KalenderPendidikanImport implements ToCollection
{
  public int $berhasil = 0;
  public int $dilewati = 0;

  /** @var string[] pesan kesalahan per baris */
  public array $errors = [];

  /** @var array<string, true> kunci baris yang sudah ada di database */
  private array $kunciAda = [];

  /** @var array<string, true> kunci baris yang sudah diproses dari file ini */
  private array $kunciFile = [];

  private string $rentangMulai;
  private string $rentangSelesai;

  public function __construct(private string $tahunPelajaran)
  {
    [$awal, $akhir] = array_map('intval', explode('/', $tahunPelajaran));
    $this->rentangMulai   = Carbon::create($awal, 7, 1)->format('Y-m-d');
    $this->rentangSelesai = Carbon::create($akhir, 6, 30)->format('Y-m-d');

    KalenderPendidikan::where('tahun_pelajaran', $tahunPelajaran)
      ->get()
      ->each(fn($k) => $this->kunciAda[$this->kunci(
        $k->tanggal_mulai->format('Y-m-d'),
        ($k->tanggal_selesai ?? $k->tanggal_mulai)->format('Y-m-d'),
        $k->jenis,
        $k->kegiatan
      )] = true);
  }

  public function collection(Collection $rows)
  {
    foreach ($rows as $i => $row) {
      $baris = $i + 1; // nomor baris di Excel (baris 1 = tajuk)

      $mulaiMentah = $row[0] ?? null;

      // Lewati baris kosong sepenuhnya
      if ($mulaiMentah === null || trim((string) $mulaiMentah) === '') {
        continue;
      }

      $mulai = $this->bacaTanggal($mulaiMentah);

      // Baris tajuk: sel pertama bukan tanggal — lewati baris 1 saja
      if ($mulai === null && $baris === 1) {
        continue;
      }

      if ($mulai === null) {
        $this->errors[] = "Baris {$baris}: tanggal mulai tidak terbaca (gunakan format tanggal Excel atau dd/mm/yyyy).";
        continue;
      }

      $selesaiMentah = $row[1] ?? null;
      if ($selesaiMentah === null || trim((string) $selesaiMentah) === '') {
        $selesai = $mulai;
      } else {
        $selesai = $this->bacaTanggal($selesaiMentah);
        if ($selesai === null) {
          $this->errors[] = "Baris {$baris}: tanggal selesai tidak terbaca.";
          continue;
        }
      }

      if ($selesai < $mulai) {
        $this->errors[] = "Baris {$baris}: tanggal selesai lebih awal dari tanggal mulai.";
        continue;
      }

      if ($mulai < $this->rentangMulai || $selesai > $this->rentangSelesai) {
        $this->errors[] = "Baris {$baris}: tanggal di luar rentang tahun pelajaran {$this->tahunPelajaran}.";
        continue;
      }

      $jenis = $this->jenisKanonik(trim((string) ($row[2] ?? '')));
      if ($jenis === null) {
        $this->errors[] = "Baris {$baris}: jenis '" . trim((string) ($row[2] ?? '')) . "' tidak dikenal. Pilihan: "
          . implode(', ', KalenderPendidikanController::JENIS_KEGIATAN) . '.';
        continue;
      }

      $libur = $this->bacaLibur($row[3] ?? null);
      if ($libur === null) {
        $this->errors[] = "Baris {$baris}: kolom Libur harus diisi Ya atau Tidak.";
        continue;
      }

      $kegiatan = trim((string) ($row[4] ?? '')) ?: null;

      $kunci = $this->kunci($mulai, $selesai, $jenis, $kegiatan);
      if (isset($this->kunciAda[$kunci]) || isset($this->kunciFile[$kunci])) {
        $this->dilewati++;
        continue;
      }
      $this->kunciFile[$kunci] = true;

      KalenderPendidikan::create([
        'tahun_pelajaran' => $this->tahunPelajaran,
        'jenis_semester'  => Carbon::parse($mulai)->month >= 7 ? 'Ganjil' : 'Genap',
        'jenis'           => $jenis,
        'tanggal_mulai'   => $mulai,
        'tanggal_selesai' => $selesai,
        'is_libur'        => $libur,
        'kegiatan'        => $kegiatan,
      ]);
      $this->berhasil++;
    }
  }

  /**
   * Baca tanggal dari sel Excel dalam rupa apa pun yang lazim
   * muncul dari file yang dibuat / disimpan ulang di aplikasi
   * berbeda: objek tanggal, angka serial Excel (juga sebagai
   * teks), string ISO ('2025-07-14', boleh dengan jam), string
   * hari/bulan/tahun ('14/07/2025', '14-07-2025', '14.07.2025',
   * satu digit boleh), dan nama bulan Indonesia/Inggris
   * ('14 Juli 2025'). Urutan selalu hari-bulan-tahun; format
   * Amerika bulan/hari sengaja TIDAK ditebak.
   */
  private function bacaTanggal($nilai): ?string
  {
    if ($nilai instanceof \DateTimeInterface) {
      return $nilai->format('Y-m-d');
    }

    if (is_numeric($nilai)) {
      try {
        return ExcelDate::excelToDateTimeObject((float) $nilai)->format('Y-m-d');
      } catch (\Throwable) {
        return null;
      }
    }

    $teks = trim(str_replace("\u{00A0}", ' ', (string) $nilai));
    if ($teks === '') {
      return null;
    }

    // Angka serial Excel yang tersimpan sebagai teks
    if (is_numeric($teks)) {
      try {
        return ExcelDate::excelToDateTimeObject((float) $teks)->format('Y-m-d');
      } catch (\Throwable) {
        return null;
      }
    }

    foreach (['Y-m-d H:i:s', 'Y-m-d', 'Y/m/d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'j/n/Y', 'j-n-Y'] as $format) {
      try {
        return Carbon::createFromFormat($format, $teks)->format('Y-m-d');
      } catch (\Throwable) {
        // coba format berikutnya
      }
    }

    // Nama bulan: '14 Juli 2025' atau 'Juli 14, 2025'
    $bulan = [
      'januari' => 1, 'january' => 1, 'jan' => 1,
      'februari' => 2, 'february' => 2, 'feb' => 2,
      'maret' => 3, 'march' => 3, 'mar' => 3,
      'april' => 4, 'apr' => 4,
      'mei' => 5, 'may' => 5,
      'juni' => 6, 'june' => 6, 'jun' => 6,
      'juli' => 7, 'july' => 7, 'jul' => 7,
      'agustus' => 8, 'august' => 8, 'agu' => 8, 'aug' => 8,
      'september' => 9, 'sept' => 9, 'sep' => 9,
      'oktober' => 10, 'october' => 10, 'okt' => 10, 'oct' => 10,
      'november' => 11, 'nov' => 11,
      'desember' => 12, 'december' => 12, 'des' => 12, 'dec' => 12,
    ];
    if (preg_match('/^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/', $teks, $m) && isset($bulan[strtolower($m[2])])) {
      return sprintf('%04d-%02d-%02d', (int) $m[3], $bulan[strtolower($m[2])], (int) $m[1]);
    }
    if (preg_match('/^([A-Za-z]+)\s+(\d{1,2}),?\s+(\d{4})$/', $teks, $m) && isset($bulan[strtolower($m[1])])) {
      return sprintf('%04d-%02d-%02d', (int) $m[3], $bulan[strtolower($m[1])], (int) $m[2]);
    }

    return null;
  }

  /** Samakan kapitalisasi jenis dengan daftar resmi; null bila tak dikenal. */
  private function jenisKanonik(string $jenis): ?string
  {
    foreach (KalenderPendidikanController::JENIS_KEGIATAN as $resmi) {
      if (strcasecmp($resmi, $jenis) === 0) {
        return $resmi;
      }
    }

    return null;
  }

  /** Kolom Libur: Ya/1/true → true, Tidak/0/false/kosong → false, lain → null. */
  private function bacaLibur($nilai): ?bool
  {
    if ($nilai === null || trim((string) $nilai) === '') {
      return false;
    }

    $teks = strtolower(trim((string) $nilai));

    return match (true) {
      in_array($teks, ['ya', 'yes', '1', 'true', 'libur'], true) => true,
      in_array($teks, ['tidak', 'no', '0', 'false', '-'], true) => false,
      default => null,
    };
  }

  private function kunci(string $mulai, string $selesai, string $jenis, ?string $kegiatan): string
  {
    return implode('|', [$mulai, $selesai, $jenis, mb_strtolower(trim((string) $kegiatan))]);
  }
}
