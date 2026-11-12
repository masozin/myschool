<?php

// database/seeders/FiturSeeder.php
namespace Database\Seeders;

use App\Models\Fitur;
use Illuminate\Database\Seeder;

class FiturSeeder extends Seeder
{
  public function run(): void
  {
    $fiturs = [
      ['kode' => 'eraport',         'nama' => 'Raport Digital',      'deskripsi' => ''],
      ['kode' => 'kaldik',          'nama' => 'Kalender Pendidikan', 'deskripsi' => ''],
      ['kode' => 'jadwal-pelajaran', 'nama' => 'Jadwal Pelajaran',   'deskripsi' => ''],
      ['kode' => 'kartu-pelajar',   'nama' => 'Kartu Pelajar',       'deskripsi' => ''],
      ['kode' => 'kartu-ujian',     'nama' => 'Kartu Ujian',         'deskripsi' => ''],
      ['kode' => 'formulir',        'nama' => 'Formulir K3S',        'deskripsi' => ''],
      ['kode' => 'skl',             'nama' => 'SKL',                 'deskripsi' => ''],
      ['kode' => 'skhu',            'nama' => 'SKHU',                'deskripsi' => ''],
      ['kode' => 'dh-siswa',        'nama' => 'Daftar Hadir Siswa',  'deskripsi' => 'Lembah daftar hadir siswa'],
      ['kode' => 'elearning',       'nama' => 'E-Learning',          'deskripsi' => ''],
      ['kode' => 'modul-ajar',      'nama' => 'Modul Ajar',          'deskripsi' => ''],
      ['kode' => 'presensi',        'nama' => 'Presensi Siswa',      'deskripsi' => ''],
      ['kode' => 'sistem-point',    'nama' => 'Sistem Point',        'deskripsi' => 'Sistem point siswa'],
    ];

    foreach ($fiturs as $f) {
      Fitur::updateOrCreate(['kode' => $f['kode']], $f);
    }
  }
}
