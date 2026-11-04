<?php

namespace Database\Seeders;

use App\Models\SkhuTemplate;
use Illuminate\Database\Seeder;

class SkhuTemplateSeeder extends Seeder
{
  public function run(): void
  {
    $templates = [
      [
        'jenjang'         => 'SD',
        'tahun_pelajaran' => '2024/2025',
        'nama_file'       => 'sd/2024-2025',
        'keterangan'      => 'Template SKHU SD Tahun Pelajaran 2024/2025 (Kurikulum Merdeka)',
        'is_active'       => true,
      ],
      [
        'jenjang'         => 'SMP',
        'tahun_pelajaran' => '2024/2025',
        'nama_file'       => 'smp/2024-2025',
        'keterangan'      => 'Template SKHU SMP Tahun Pelajaran 2024/2025 (Kurikulum Merdeka)',
        'is_active'       => true,
      ],
      [
        'jenjang'         => 'SMA',
        'tahun_pelajaran' => '2024/2025',
        'nama_file'       => 'sma/2024-2025',
        'keterangan'      => 'Template SKHU SMA Tahun Pelajaran 2024/2025 (Kurikulum Merdeka)',
        'is_active'       => true,
      ],
    ];

    foreach ($templates as $t) {
      SkhuTemplate::updateOrCreate(
        ['jenjang' => $t['jenjang'], 'tahun_pelajaran' => $t['tahun_pelajaran']],
        $t
      );
    }
  }
}
