<?php

namespace Database\Seeders;

use App\Models\Kurikulum;
use App\Models\Semester;
use App\Models\SkhuTemplate;
use Illuminate\Database\Seeder;

class SkhuTemplateSeeder extends Seeder
{
  public function run(): void
  {
    // Skema tabel_skhu_template yang sebenarnya: jenjang + semester_id +
    // kurikulum_id. Versi lama seeder ini menulis kolom 'tahun_pelajaran'
    // yang tidak pernah ada di tabel, sehingga tidak pernah berhasil
    // mendaftarkan template apa pun.
    $kurikulum = Kurikulum::where('kode', 'MERDEKA')->first();

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
      // SKHU diterbitkan di semester genap tahun pelajaran tersebut
      $semester = Semester::where('tahun_pelajaran', $t['tahun_pelajaran'])
        ->where('semester', 'Genap')
        ->first();

      if (!$semester || !$kurikulum) {
        continue; // semester/kurikulum belum ada — lewati, jangan gagal
      }

      SkhuTemplate::updateOrCreate(
        ['jenjang' => $t['jenjang'], 'semester_id' => $semester->id],
        [
          'kurikulum_id' => $kurikulum->id,
          'nama_file'    => $t['nama_file'],
          'keterangan'   => $t['keterangan'],
          'is_active'    => $t['is_active'],
        ]
      );
    }
  }
}
