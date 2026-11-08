<?php

namespace App\Imports;

use App\Models\Siswa;
use App\Models\SiswaRegistrasi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SiswaImport implements ToCollection, SkipsEmptyRows, WithMultipleSheets
{
  protected int   $sekolahId;
  protected array $errors   = [];
  protected int   $berhasil = 0;

  protected array $kolomWajib = [
    'nama_siswa',
    'nisn',
    'alamat',
    'jenis_kelamin',
    'tempat_lahir',
    'tgl_lahir',
    'agama',
    'nama_ibu',
    'nis',
    'jenis_pendaftaran',
    'tingkat',
    'tanggal_diterima',
  ];

  public function __construct(int $sekolahId)
  {
    $this->sekolahId = $sekolahId;
  }

  // ── Hanya baca sheet pertama (index 0 = "Data Siswa") ───────────────────
  public function sheets(): array
  {
    return [
      0 => $this, // index 0 = sheet pertama
    ];
  }

  public function collection(Collection $rows)
  {
    if ($rows->isEmpty()) {
      $this->errors[] = $this->err('-', 'File', '-', 'File kosong.');
      return;
    }

    // ── Baris 1: Heading ─────────────────────────────────────────────────
    $headingBaris = $rows->get(0)->toArray();

    $fieldToIndex = [];
    foreach ($headingBaris as $index => $namaKolom) {
      $key = strtolower(trim((string)$namaKolom));
      $key = str_replace('*', '', $key);
      $key = str_replace(' ', '_', trim($key));
      $fieldToIndex[$key] = $index;
    }

    // Pastikan kolom wajib ada
    $tidakAda = array_diff($this->kolomWajib, array_keys($fieldToIndex));
    if (!empty($tidakAda)) {
      $this->errors[] = $this->err(
        '-',
        'Heading',
        '-',
        'Kolom tidak ditemukan: ' . implode(', ', $tidakAda) .
          '. Pastikan menggunakan template yang benar.'
      );
      return;
    }

    // ── Deteksi baris keterangan ─────────────────────────────────────────
    $baris2     = $rows->get(1);
    $nisnIdx    = $fieldToIndex['nisn'];
    $nisnBaris2 = trim((string)($baris2[$nisnIdx] ?? ''));
    $isKeterangan = !preg_match('/^\d+$/', $nisnBaris2);

    $startIndex  = $isKeterangan ? 2 : 1;
    $offsetBaris = $isKeterangan ? 3 : 2;
    $dataRows    = $rows->slice($startIndex)->values();

    // ── Konversi index → field ────────────────────────────────────────────
    $dataNormalized = $dataRows->map(function ($row) use ($fieldToIndex) {
      $result = [];
      foreach ($fieldToIndex as $field => $index) {
        $result[$field] = $this->bersihkanNilai($row[$index] ?? null);
      }
      return $result;
    });

    // ── Tahap 1: Validasi ─────────────────────────────────────────────────
    $nisnDiFile = [];
    $nisDiFile  = [];

    foreach ($dataNormalized as $index => $rowData) {
      $noBaris   = $offsetBaris + $index;
      $rowErrors = $this->validateRow($rowData, $noBaris, $nisnDiFile, $nisDiFile);

      $nisn = trim((string)($rowData['nisn'] ?? ''));
      $nis  = trim((string)($rowData['nis']  ?? ''));
      if ($nisn) $nisnDiFile[] = $nisn;
      if ($nis)  $nisDiFile[]  = $nis;

      foreach ($rowErrors as $error) {
        $this->errors[] = $error;
      }
    }

    if (!empty($this->errors)) {
      return;
    }

    // ── Tahap 2: Insert ───────────────────────────────────────────────────
    DB::beginTransaction();
    try {
      foreach ($dataNormalized as $rowData) {
        $this->insertRow($rowData);
      }
      DB::commit();
      $this->berhasil = $dataNormalized->count();
    } catch (\Exception $e) {
      DB::rollBack();
      $this->errors[] = $this->err(
        '-',
        'Sistem',
        '-',
        'Terjadi kesalahan sistem: ' . $e->getMessage()
      );
    }
  }

  private function validateRow(array $row, int $noBaris, array $nisnDiFile, array $nisDiFile): array
  {
    $errors = [];
    $val    = fn($key) => trim((string)($row[$key] ?? ''));

    // nama_ayah sengaja TIDAK wajib diisi: boleh kosong (kolom database
    // memang nullable), misalnya bila nama ayah tidak diketahui.
    $wajib = [
      'nama_siswa'        => 'Nama Siswa',
      'nisn'              => 'NISN',
      'alamat'            => 'Alamat',
      'jenis_kelamin'     => 'Jenis Kelamin',
      'tempat_lahir'      => 'Tempat Lahir',
      'tgl_lahir'         => 'Tanggal Lahir',
      'agama'             => 'Agama',
      'nama_ibu'          => 'Nama Ibu',
      'nis'               => 'NIS',
      'jenis_pendaftaran' => 'Jenis Pendaftaran',
      'tingkat'           => 'Tingkat',
      'tanggal_diterima'  => 'Tanggal Diterima',
    ];

    foreach ($wajib as $key => $label) {
      if (empty($val($key))) {
        $errors[] = $this->err($noBaris, $label, '-', "{$label} wajib diisi.");
      }
    }

    $nisn = $val('nisn');
    if ($nisn) {
      if (!preg_match('/^\d{10}$/', $nisn)) {
        $errors[] = $this->err($noBaris, 'NISN', $nisn, 'NISN harus tepat 10 digit angka.');
      } elseif (Siswa::where('nisn', $nisn)->exists()) {
        $errors[] = $this->err($noBaris, 'NISN', $nisn, 'NISN sudah terdaftar di database.');
      } elseif (in_array($nisn, $nisnDiFile)) {
        $errors[] = $this->err($noBaris, 'NISN', $nisn, 'NISN duplikat dalam file Excel.');
      }
    }

    $nik = $val('nik');
    if ($nik && !preg_match('/^\d{16}$/', $nik)) {
      $errors[] = $this->err($noBaris, 'NIK', $nik, 'NIK harus 16 digit angka.');
    }

    $jk = strtoupper($val('jenis_kelamin'));
    if ($jk && !in_array($jk, ['L', 'P'])) {
      $errors[] = $this->err($noBaris, 'Jenis Kelamin', $jk, 'Harus L atau P.');
    }

    $agama      = $val('agama');
    $agamaValid = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Khonghucu'];
    if ($agama && !in_array(ucfirst(strtolower($agama)), $agamaValid)) {
      $errors[] = $this->err(
        $noBaris,
        'Agama',
        $agama,
        'Tidak valid. Pilihan: ' . implode(', ', $agamaValid)
      );
    }

    $tglLahir = $val('tgl_lahir');
    if ($tglLahir && !$this->isValidDate($row['tgl_lahir'])) {
      $errors[] = $this->err(
        $noBaris,
        'Tanggal Lahir',
        $tglLahir,
        'Format tidak valid. Gunakan YYYY-MM-DD.'
      );
    }

    $jenPend = $val('jenis_pendaftaran');
    if ($jenPend && !in_array(ucfirst(strtolower($jenPend)), ['Baru', 'Pindahan'])) {
      $errors[] = $this->err(
        $noBaris,
        'Jenis Pendaftaran',
        $jenPend,
        'Harus Baru atau Pindahan.'
      );
    }

    $tingkat = $val('tingkat');
    if ($tingkat !== '' && ((int)$tingkat < 1 || (int)$tingkat > 12)) {
      $errors[] = $this->err($noBaris, 'Tingkat', $tingkat, 'Harus antara 1-12.');
    }

    $tglDiterima = $val('tanggal_diterima');
    if ($tglDiterima && !$this->isValidDate($row['tanggal_diterima'])) {
      $errors[] = $this->err(
        $noBaris,
        'Tanggal Diterima',
        $tglDiterima,
        'Format tidak valid. Gunakan YYYY-MM-DD.'
      );
    }

    $nis = $val('nis');
    if ($nis) {
      if (SiswaRegistrasi::where('nis', $nis)->where('sekolah_id', $this->sekolahId)->exists()) {
        $errors[] = $this->err($noBaris, 'NIS', $nis, 'NIS sudah terdaftar di sekolah ini.');
      } elseif (in_array($nis, $nisDiFile)) {
        $errors[] = $this->err($noBaris, 'NIS', $nis, 'NIS duplikat dalam file Excel.');
      }
    }

    return $errors;
  }

  private function insertRow(array $row): void
  {
    $val = fn($key) => trim((string)($row[$key] ?? '')) ?: null;

    $namaWali      = $val('nama_wali')      ?? $val('nama_ayah');
    $nikWali       = $val('nik_wali')       ?? $val('nik_ayah');
    $tahunWali     = $val('tahun_wali')     ?? $val('tahun_ayah');
    $pekerjaanWali = $val('pekerjaan_wali') ?? $val('pekerjaan_ayah');
    $noHpWali      = $val('no_hp_wali')     ?? $val('no_hp_ayah');

    $agama            = ucfirst(strtolower($val('agama') ?? ''));
    $jenisPendaftaran = ucfirst(strtolower($val('jenis_pendaftaran') ?? ''));

    $siswa = Siswa::create([
      'nama_siswa'     => $val('nama_siswa'),
      'nisn'           => $val('nisn'),
      'nik'            => $val('nik'),
      'jenis_kelamin'  => strtoupper($val('jenis_kelamin') ?? ''),
      'tempat_lahir'   => $val('tempat_lahir'),
      'tgl_lahir'      => $this->parseDate($row['tgl_lahir'] ?? ''),
      'agama'          => $agama,
      'alamat'         => $val('alamat'),
      'disabilitas'    => $val('disabilitas') ?? 'Tidak ada',
      'nama_ayah'      => $val('nama_ayah'),
      'nik_ayah'       => $val('nik_ayah'),
      'tahun_ayah'     => $val('tahun_ayah'),
      'pekerjaan_ayah' => $val('pekerjaan_ayah'),
      'nama_ibu'       => $val('nama_ibu'),
      'nik_ibu'        => $val('nik_ibu'),
      'tahun_ibu'      => $val('tahun_ibu'),
      'pekerjaan_ibu'  => $val('pekerjaan_ibu'),
      'no_hp'          => $val('no_hp_ayah'),
      'nama_wali'      => $namaWali,
      'nik_wali'       => $nikWali,
      'tahun_wali'     => $tahunWali,
      'pekerjaan_wali' => $pekerjaanWali,
      'no_hp_wali'     => $noHpWali,
      'status'         => 'aktif',
    ]);

    SiswaRegistrasi::create([
      'siswa_id'          => $siswa->id,
      'sekolah_id'        => $this->sekolahId,
      'nis'               => $val('nis'),
      'jenis_pendaftaran' => $jenisPendaftaran,
      'tingkat'           => (int)($row['tingkat'] ?? 0),
      'tanggal_diterima'  => $this->parseDate($row['tanggal_diterima'] ?? ''),
      'status'            => 'aktif',
    ]);
  }

  private function err(int|string $baris, string $kolom, string $nilai, string $pesan): array
  {
    return compact('baris', 'kolom', 'nilai', 'pesan');
  }

  /**
   * Nilai sampah khas file ekspor (sisa rumus yang gagal seperti
   * '#N/A' / '#VALUE!', tanda '?' / '-' pengisi kolom kosong)
   * dianggap KOSONG. Tanpa ini sampah tersebut lolos validasi
   * dan tersimpan apa adanya — atau, untuk kolom NOT NULL
   * seperti alamat, meledak sebagai galat sistem yang
   * menggagalkan seluruh impor.
   */
  private function bersihkanNilai($nilai)
  {
    if (!is_string($nilai)) {
      return $nilai;
    }

    $teks = trim($nilai);
    $sampah = ['#N/A', '#VALUE!', '#REF!', '#NAME?', '#DIV/0!', 'N/A', '?', '-'];

    return in_array(strtoupper($teks), $sampah, true) ? null : $nilai;
  }

  private function isValidDate($value): bool
  {
    if (empty($value)) return false;
    if (is_numeric($value)) return true;
    $d = \DateTime::createFromFormat('Y-m-d', $value);
    return $d && $d->format('Y-m-d') === $value;
  }

  private function parseDate($value): ?string
  {
    if (empty($value)) return null;
    if (is_numeric($value)) {
      return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
        ->format('Y-m-d');
    }
    return $value;
  }

  public function getErrors(): array
  {
    return $this->errors;
  }
  public function getBerhasil(): int
  {
    return $this->berhasil;
  }
  public function hasErrors(): bool
  {
    return !empty($this->errors);
  }
}
