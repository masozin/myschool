<?php

// database/seeders/RolesSeeder.php
namespace Database\Seeders;

use App\Models\Ptk;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
  public function run(): void
  {
    // Daftar role baku dibuat eksplisit di sini (idempoten) agar kode
    // tidak lagi bergantung pada Role::firstOrCreate dari teks jabatan
    // bebas. Sumber daftarnya: konstanta Ptk::PERAN_BAKU.
    foreach (Ptk::PERAN_BAKU as $namaPeran) {
      Role::firstOrCreate(['name' => $namaPeran, 'guard_name' => 'web']);
    }
  }
}
