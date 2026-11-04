<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Semester>
 */
class SemesterFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    $tahunSekarang = 24;
    $tahunDepan = 25;
    $semesterKode = fake()->randomElement(['1', '2']);

    return [
      'kode_semester' => $tahunSekarang . $tahunDepan . $semesterKode,
      'nama_semester' => $semesterKode == '1' ? 'Ganjil' : 'Genap',
      'tahun_pelajaran' => "20$tahunSekarang/20$tahunDepan",
    ];
  }
}
