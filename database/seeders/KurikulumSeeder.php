<?php

namespace Database\Seeders;

use App\Models\Kurikulum;
use Illuminate\Database\Seeder;

class KurikulumSeeder extends Seeder
{
  public function run(): void
  {
    $data = [
      ['kode' => 'KTSP',     'nama' => 'Kurikulum Tingkat Satuan Pendidikan', 'tahun_berlaku' => 2006, 'is_aktif' => false],
      ['kode' => 'K13',      'nama' => 'Kurikulum 2013',                       'tahun_berlaku' => 2013, 'is_aktif' => false],
      ['kode' => 'K13-REV',  'nama' => 'Kurikulum 2013 Revisi',                'tahun_berlaku' => 2017, 'is_aktif' => true],
      ['kode' => 'MERDEKA',  'nama' => 'Kurikulum Merdeka',                    'tahun_berlaku' => 2022, 'is_aktif' => true],
    ];

    foreach ($data as $row) {
      Kurikulum::updateOrCreate(['kode' => $row['kode']], $row);
    }
  }
}
