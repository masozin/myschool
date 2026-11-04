<?php

namespace Database\Seeders;

use App\Models\MapelMaster;
use App\Models\MapelMapping;
use App\Models\Semester;
use Illuminate\Database\Seeder;

class MapelMappingSeeder extends Seeder
{
  public function run(): void
  {
    $semesters = Semester::all();
    if ($semesters->isEmpty()) {
      $this->command->warn('Tabel semester kosong — jalankan SemesterSeeder dulu.');
      return;
    }

    $masters = MapelMaster::with('mapelJenjang')->get();

    $urutan = [
      'AGM-ISLAM' => 1,
      'AGM-KRISTEN' => 1,
      'AGM-KATOLIK' => 1,
      'AGM-HINDU' => 1,
      'AGM-BUDDHA' => 1,
      'AGM-KHONGHUCU' => 1,
      'PPKN' => 2,
      'BIN' => 3,
      'MTK' => 4,
      'IPAS' => 5,
      'IPA-SD' => 5,
      'IPA' => 5,
      'IPS-SD' => 6,
      'IPS' => 6,
      'BING' => 7,
      'PJOK' => 8,
      'SENI' => 9,
      'INFORMATIKA' => 10,
      'SEJARAH' => 11,
      'PRAKARYA' => 12,
      'P5' => 13,
    ];

    $tingkatPerJenjang = [
      'SD'  => range(1, 6),
      'SMP' => range(7, 9),
      'SMA' => range(10, 12),
      'SMK' => range(10, 13),
    ];

    $rows = [];

    foreach ($tingkatPerJenjang as $jenjang => $tingkatList) {
      foreach ($tingkatList as $tingkat) {
        foreach ($semesters as $semester) {
          foreach ($masters as $master) {
            $cocok = $master->mapelJenjang->first(function ($mj) use ($jenjang, $tingkat) {
              return $mj->jenjang === $jenjang
                && $tingkat >= $mj->kelas_mulai
                && $tingkat <= $mj->kelas_selesai;
            });

            if (!$cocok) {
              continue;
            }

            $rows[] = [
              'mapel_master_id' => $master->id,
              'jenjang'         => $jenjang,
              'tingkat'         => $tingkat,
              'semester_id'     => $semester->id,
              'jenis'           => 'pokok',
              'sekolah_id'      => null,
              'urutan'          => $urutan[$master->kode] ?? 99,
              'created_at'      => now(),
              'updated_at'      => now(),
            ];
          }
        }
      }
    }

    collect($rows)->chunk(500)->each(
      fn($chunk) => MapelMapping::upsert(
        $chunk->toArray(),
        ['mapel_master_id', 'jenjang', 'tingkat', 'semester_id', 'sekolah_id'],
        ['jenis', 'urutan', 'updated_at']
      )
    );

    $this->command->info('✅ MapelMappingSeeder selesai: ' . count($rows) . ' baris.');
  }
}
