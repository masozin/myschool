<?php

namespace App\Imports;

// use App\Models\Mapel;
use App\Models\MapelMapping;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Skhu;
use App\Models\SkhuNilai;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class SkhuImport implements ToCollection, SkipsEmptyRows, WithMultipleSheets
{
  protected int    $sekolahId;
  protected int    $semesterId;
  protected string $jenjang;
  protected string $tahunPelajaran;
  protected array  $dataHeader;
  protected array  $errors     = [];
  protected int    $berhasil   = 0;
  protected int    $diperbarui = 0;

  public function __construct(
    int    $sekolahId,
    int    $semesterId,
    string $jenjang,
    string $tahunPelajaran,
    array  $dataHeader
  ) {
    $this->sekolahId      = $sekolahId;
    $this->semesterId     = $semesterId;
    $this->jenjang        = $jenjang;
    $this->tahunPelajaran = $tahunPelajaran;
    $this->dataHeader     = $dataHeader;
  }

  public function sheets(): array
  {
    return [0 => $this];
  }

  public function collection(Collection $rows)
  {
    if ($rows->isEmpty()) {
      $this->errors[] = $this->err('-', 'File', '-', 'File kosong.');
      return;
    }

    // ── Baris 1: Heading ─────────────────────────────────────────────────
    // Format heading:
    //   Kolom tetap  : "No", "Nama Siswa", "NISN", "Rombel"
    //   Kolom nomor  : "nomor_surat||Nomor Surat", "nomor_ujian||Nomor Ujian"
    //   Kolom mapel  : "mapel_{id}||Nama Mapel"
    $heading = $rows->get(0)->toArray();

    $nisnIdx     = null;  // index kolom NISN
    $nomorKolom  = [];    // ['nomor_surat' => idx, 'nomor_ujian' => idx]
    $mapelKolom  = [];    // [idx => mapel_id]

    foreach ($heading as $idx => $cell) {
      $cell = trim((string) $cell);

      if ($cell === 'NISN') {
        $nisnIdx = $idx;
      } elseif (str_starts_with($cell, 'nomor_surat||') || $cell === 'nomor_surat') {
        $nomorKolom['nomor_surat'] = $idx;
      } elseif (str_starts_with($cell, 'nomor_ujian||') || $cell === 'nomor_ujian') {
        $nomorKolom['nomor_ujian'] = $idx;
      } elseif (str_starts_with($cell, 'mapel_mapping_')) {
        $parts   = explode('||', $cell);
        $mapelId = (int) str_replace('mapel_mapping_', '', $parts[0]);
        if ($mapelId > 0) {
          $mapelKolom[$idx] = $mapelId;
        }
      }
    }

    if ($nisnIdx === null) {
      $this->errors[] = $this->err('-', 'Template', '-', 'Kolom NISN tidak ditemukan. Gunakan template yang benar.');
      return;
    }

    if (empty($mapelKolom)) {
      $this->errors[] = $this->err('-', 'Template', '-', 'Tidak ada kolom mapel ditemukan. Gunakan template yang benar.');
      return;
    }

    // ── Mulai dari Baris ke-5 (Index 4) ───────────────────────────────
    $startIdx = 4;
    $offset   = 5;

    $dataRows = $rows->slice($startIdx)->values();

    // ── Validasi mapel di DB ──────────────────────────────────────────────
    $mapelIds   = array_values($mapelKolom);
    $mapelValid = MapelMapping::whereIn('id', $mapelIds)->pluck('id')->toArray();

    if (!empty(array_diff($mapelIds, $mapelValid))) {
      $this->errors[] = $this->err('-', 'Template', '-', 'Beberapa ID mapel tidak valid. Generate ulang template.');
      return;
    }

    // ── Nama kolom untuk pesan error ─────────────────────────────────────
    $mapelMappings = MapelMapping::with('masterMapel')->whereIn('id', $mapelIds)->get();
    $mapelNama = [];
    foreach ($mapelMappings as $mapping) {
      $mapelNama[$mapping->id] = $mapping->masterMapel->nama ?? "Mapel #{$mapping->id}";
    }

    // ── Tahap 1: Validasi semua baris ─────────────────────────────────────
    $nisnDiFile = [];

    foreach ($dataRows as $i => $row) {
      $noBaris = $offset + $i;
      $nisn    = trim((string) ($row[$nisnIdx] ?? ''));

      // Skip baris benar-benar kosong
      if ($nisn === '' && trim((string) ($row[1] ?? '')) === '') continue;

      $rowErrors = $this->validateRow($row, $noBaris, $nisnIdx, $mapelKolom, $mapelNama, $nisnDiFile);
      if ($nisn !== '') $nisnDiFile[] = $nisn;

      foreach ($rowErrors as $e) {
        $this->errors[] = $e;
      }
    }

    if (!empty($this->errors)) return;

    // ── Tahap 2: Upsert ───────────────────────────────────────────────────
    DB::beginTransaction();
    try {
      foreach ($dataRows as $row) {
        $nisn = trim((string) ($row[$nisnIdx] ?? ''));
        if ($nisn === '') continue;
        $this->upsertSkhu($row, $nisnIdx, $nomorKolom, $mapelKolom);
      }
      DB::commit();
    } catch (\Exception $e) {
      DB::rollBack();
      $this->errors[] = $this->err('-', 'Sistem', '-', 'Kesalahan sistem: ' . $e->getMessage());
    }
  }

  // ── Validasi satu baris ───────────────────────────────────────────────────
  private function validateRow(
    $row,
    int   $noBaris,
    int   $nisnIdx,
    array $mapelKolom,
    $mapelNama,
    array $nisnDiFile
  ): array {
    $errors = [];
    $val    = fn($idx) => trim((string) ($row[$idx] ?? ''));

    $nama = $val(1);
    $nisn = $val($nisnIdx);

    if (empty($nama)) {
      $errors[] = $this->err($noBaris, 'Nama Siswa', '-', 'Nama siswa tidak boleh kosong.');
    }

    if (empty($nisn)) {
      $errors[] = $this->err($noBaris, 'NISN', '-', 'NISN tidak boleh kosong.');
    } elseif (!preg_match('/^\d{10}$/', $nisn)) {
      $errors[] = $this->err($noBaris, 'NISN', $nisn, 'NISN harus 10 digit angka.');
    } else {
      if (in_array($nisn, $nisnDiFile)) {
        $errors[] = $this->err($noBaris, 'NISN', $nisn, 'NISN duplikat dalam file Excel.');
      }
      if (!Siswa::where('nisn', $nisn)->exists()) {
        $errors[] = $this->err($noBaris, 'NISN', $nisn, 'Siswa dengan NISN ini tidak ditemukan.');
      }
    }

    // Validasi nilai mapel
    foreach ($mapelKolom as $idx => $mapelId) {
      $nilai = $val($idx);
      if ($nilai === '') continue; // kosong = skip

      if (!is_numeric($nilai)) {
        $namaMapel = $mapelNama[$mapelId] ?? "Mapel #{$mapelId}";
        $errors[]  = $this->err($noBaris, $namaMapel, $nilai, 'Nilai harus berupa angka.');
      } elseif ((float) $nilai < 0 || (float) $nilai > 100) {
        $namaMapel = $mapelNama[$mapelId] ?? "Mapel #{$mapelId}";
        $errors[]  = $this->err($noBaris, $namaMapel, $nilai, 'Nilai harus antara 0 dan 100.');
      }
    }

    return $errors;
  }

  // ── Upsert SKHU + nilai ───────────────────────────────────────────────────
  private function upsertSkhu($row, int $nisnIdx, array $nomorKolom, array $mapelKolom): void
  {
    $val   = fn($idx) => trim((string) ($row[$idx] ?? '')) ?: null;
    $nisn  = trim((string) ($row[$nisnIdx] ?? ''));
    $siswa = Siswa::where('nisn', $nisn)->first();
    if (!$siswa) return;

    // Ambil nilai nomor surat & nomor ujian dari file jika kolomnya ada
    $nomorSurat = isset($nomorKolom['nomor_surat'])
      ? ($val($nomorKolom['nomor_surat']) ?: null)
      : null;
    $nomorUjian = isset($nomorKolom['nomor_ujian'])
      ? ($val($nomorKolom['nomor_ujian']) ?: null)
      : null;

    $skhu     = Skhu::where('siswa_id', $siswa->id)
      ->where('semester_id', $this->semesterId)
      ->first();
    $isUpdate = (bool) $skhu;

    $headerData = [
      'tanggal_mulai_ujian'   => $this->dataHeader['tanggal_mulai_ujian'] ?: null,
      'tanggal_selesai_ujian' => $this->dataHeader['tanggal_selesai_ujian'] ?: null,
      'tanggal_terbit'        => $this->dataHeader['tanggal_terbit'] ?: null,
      'status'                => 'draft',
      'generated_at'          => now(),
    ];

    // Nomor surat/ujian: update hanya jika kolom ada di file dan tidak kosong
    if ($nomorSurat !== null) $headerData['nomor_surat'] = $nomorSurat;
    if ($nomorUjian !== null) $headerData['nomor_ujian'] = $nomorUjian;

    if ($isUpdate) {
      $skhu->update($headerData);
    } else {
      $skhu = Skhu::create(array_merge($headerData, [
        'siswa_id'   => $siswa->id,
        'sekolah_id' => $this->sekolahId,
        'semester_id' => $this->semesterId,
        // Nomor surat/ujian jika ada di file, null jika tidak
        'nomor_surat' => $nomorSurat,
        'nomor_ujian' => $nomorUjian,
      ]));
    }

    // Upsert nilai per mapel
    foreach ($mapelKolom as $idx => $mapelId) {
      $nilaiRaw = trim((string) ($row[$idx] ?? ''));
      if ($nilaiRaw === '') continue; // kosong = tidak diubah

      SkhuNilai::updateOrCreate(
        ['skhu_id' => $skhu->id, 'mapel_mapping_id' => $mapelId],
        ['nilai'   => (float) $nilaiRaw]
      );
    }

    $isUpdate ? $this->diperbarui++ : $this->berhasil++;
  }

  private function err(int|string $baris, string $kolom, string $nilai, string $pesan): array
  {
    return compact('baris', 'kolom', 'nilai', 'pesan');
  }

  public function getErrors(): array
  {
    return $this->errors;
  }
  public function getBerhasil(): int
  {
    return $this->berhasil;
  }
  public function getDiperbarui(): int
  {
    return $this->diperbarui;
  }
  public function hasErrors(): bool
  {
    return !empty($this->errors);
  }
}
