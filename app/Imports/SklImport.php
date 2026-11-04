<?php

namespace App\Imports;

use App\Models\Skl;
use App\Models\SklNilai;
use App\Models\SklNilaiKomponen;
use App\Models\SklNilaiSubKomponen;
use App\Models\SklKonfigurasi;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\SkipsUnknownSheets;

class SklImport implements ToCollection, WithMultipleSheets, SkipsUnknownSheets
{
  protected $sekolahId;
  protected $semesterId;
  protected $modeInput;
  protected $mappings;
  protected $komponens;

  public $berhasil = 0;
  public $dilewati = 0;

  // Menyimpan ID siswa yang sudah diproses agar counter "berhasil" akurat
  // meski ada multiple sheet
  private $processedSiswaIds = [];

  public function __construct($sekolahId, $semesterId, $modeInput, $mappings, $komponens)
  {
    $this->sekolahId  = $sekolahId;
    $this->semesterId = $semesterId;
    $this->modeInput  = $modeInput;
    $this->mappings   = $mappings;
    $this->komponens  = $komponens;
  }

  // Eksekusi per Sheet
  public function collection(Collection $rows)
  {
    if (!isset($rows[0])) return;

    $headerKeys = $rows[0]->toArray();

    // Data dimulai dari Baris 4 (Index 3 di array)
    for ($i = 3; $i < count($rows); $i++) {
      $row = $rows[$i]->toArray();

      $siswaId = $row[0] ?? null;

      if (!$siswaId || !is_numeric($siswaId)) {
        continue;
      }

      // 1. Cari atau Generate SKL
      $skl = Skl::firstOrCreate(
        [
          'siswa_id'    => $siswaId,
          'sekolah_id'  => $this->sekolahId,
          'semester_id' => $this->semesterId,
        ],
        [
          'mode_input' => $this->modeInput,
          'status'     => 'draft',
        ]
      );

      // 2. Jika SKL baru saja terbuat (Siswa belum punya), GENERATE STRUKTUR KOSONG
      if ($skl->wasRecentlyCreated) {
        foreach ($this->mappings as $mapping) {
          $sklNilai = SklNilai::create([
            'skl_id'           => $skl->id,
            'mapel_mapping_id' => $mapping->id,
            'nilai_akhir'      => null,
          ]);

          if ($this->modeInput === 'manual') {
            foreach ($this->komponens as $komponen) {
              $sklNilaiKomponen = SklNilaiKomponen::create([
                'skl_nilai_id' => $sklNilai->id,
                'komponen_id'  => $komponen->id,
                'nilai'        => null,
              ]);

              foreach ($komponen->subKomponen as $sub) {
                SklNilaiSubKomponen::create([
                  'skl_nilai_komponen_id' => $sklNilaiKomponen->id,
                  'sub_komponen_id'       => $sub->id,
                  'nilai'                 => null,
                ]);
              }
            }
          }
        }
      }

      // Lewati jika sudah disetujui
      if ($skl->status === 'disetujui') {
        if (!in_array($siswaId, $this->processedSiswaIds)) {
          $this->dilewati++;
          $this->processedSiswaIds[] = $siswaId;
        }
        continue;
      }

      // Kunci konfigurasi karena draft SKL sudah dipastikan ada
      SklKonfigurasi::where('sekolah_id', $this->sekolahId)
        ->where('semester_id', $this->semesterId)
        ->update(['is_terkunci' => true]);

      // Update Nomor SKL (Kolom E / Index 4)
      if (!empty($row[4])) {
        $skl->update(['nomor_skl' => $row[4]]);
      }

      // 3. Looping untuk Mengisi / Menimpa Nilai dari Excel
      $memilikiPerubahanNilai = false;
      foreach ($row as $colIndex => $nilaiExcel) {
        if ($colIndex < 5 || trim((string)$nilaiExcel) === '') continue;

        $key = $headerKeys[$colIndex] ?? '';
        $nilaiClean = (float) str_replace(',', '.', $nilaiExcel);
        if ($nilaiClean < 0) $nilaiClean = 0;
        if ($nilaiClean > 100) $nilaiClean = 100;

        // Membaca brankas ID: mapel_1_komp_2_sub_3
        if (preg_match('/mapel_(\d+)(?:_komp_(\d+))?(?:_sub_(\d+))?/', $key, $matches)) {
          $mapelId = $matches[1] ?? null;
          $kompId  = $matches[2] ?? null;
          $subId   = $matches[3] ?? null;

          if ($mapelId) {
            $sklNilai = SklNilai::firstOrCreate([
              'skl_id'           => $skl->id,
              'mapel_mapping_id' => $mapelId,
            ]);

            if ($this->modeInput === 'jadi') {
              $sklNilai->update([
                'nilai_akhir' => $nilaiClean,
                'nilai_huruf' => SklNilai::angkaKeHuruf($nilaiClean)
              ]);
            } else if ($this->modeInput === 'manual' && $kompId) {
              $sklNilaiKomponen = SklNilaiKomponen::firstOrCreate([
                'skl_nilai_id' => $sklNilai->id,
                'komponen_id'  => $kompId,
              ]);

              if ($subId) {
                SklNilaiSubKomponen::updateOrCreate(
                  [
                    'skl_nilai_komponen_id' => $sklNilaiKomponen->id,
                    'sub_komponen_id'       => $subId,
                  ],
                  ['nilai' => $nilaiClean]
                );
              } else {
                $sklNilaiKomponen->update(['nilai' => $nilaiClean]);
              }
              $memilikiPerubahanNilai = true;
            }
          }
        }
      }

      // Jika mode manual dan ada nilai yang diinput, picu hitung ulang
      if ($memilikiPerubahanNilai && $this->modeInput === 'manual') {
        foreach ($skl->nilais as $n) {
          foreach ($n->komponens as $kn) {
            if ($kn->komponen->subKomponen->isNotEmpty()) {
              $kn->refresh()->hitungDariSub();
            }
          }
          $n->refresh()->hitungNilaiAkhir();
        }
      }

      if (!in_array($siswaId, $this->processedSiswaIds)) {
        $this->berhasil++;
        $this->processedSiswaIds[] = $siswaId;
      }
    }
  }

  // MULTIPLE SHEETS DYNAMIC HANDLING
  public function sheets(): array
  {
    $sheets = [];

    if ($this->modeInput === 'jadi') {
      // Mode Jadi: Hanya ada 1 Sheet Excel
      $sheets[0] = $this;
    } else {
      // Mode Manual: Jumlah Sheet bergantung pada jumlah Komponen yang ada
      // Kita petakan setiap Index Sheet (0, 1, 2, dst) dengan Object Class ini sendiri.
      // Dengan begitu, Maatwebsite Excel akan me-looping dan mengulang proses Import
      // di atas untuk SETIAP sheet yang terdeteksi di Excel.
      $jumlahSheet = $this->komponens->count();
      for ($i = 0; $i < $jumlahSheet; $i++) {
        $sheets[$i] = $this;
      }
    }

    return $sheets;
  }

  public function onUnknownSheet($sheetName)
  {
    // Abaikan sheet di luar jangkauan (misal jika ada sheet kosong tambahan)
  }
}
