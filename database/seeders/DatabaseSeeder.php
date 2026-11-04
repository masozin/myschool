<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Semester;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Laravolt\Indonesia\Seeds\ProvincesSeeder;
use Laravolt\Indonesia\Seeds\CitiesSeeder;
use Laravolt\Indonesia\Seeds\DistrictsSeeder;
use Laravolt\Indonesia\Seeds\VillagesSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);

    $admin = User::create([
      'name' => 'Administrator',
      'user_name' => "admin",
      'email' => 'admin@example.com',
      'password' => Hash::make('password'), // Pastikan password diketahui
    ]);
    $admin->assignRole($roleAdmin);

    $dataSemester = [
      ['kode' => '24251', 'semester' => 'Ganjil', 'thn' => '2024/2025', 'active' => false],
      ['kode' => '24252', 'semester' => 'Genap', 'thn' => '2024/2025', 'active' => true],
    ];

    foreach ($dataSemester as $ds) {
      Semester::create([
        'kode_semester' => $ds['kode'],
        'semester' => $ds['semester'],
        'tahun_pelajaran' => $ds['thn'],
        'is_active' => $ds['active']
      ]);
    }

    $this->call([
      ProvincesSeeder::class,
      CitiesSeeder::class,
      DistrictsSeeder::class,
      VillagesSeeder::class,

      KurikulumSeeder::class,
      MapelSeeder::class,
      MapelMappingSeeder::class,
      skhutemplateSeeder::class,

      fiturSeeder::class,
      learningSeeder::class,
    ]);
  }
}
