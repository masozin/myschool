<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rombel>
 */
class RombelFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'nama_rombel' => fake()->randomElement(['Kelas 1.A', 'Kelas 1.B', 'Kelas 2']),
      'sekolah_id' => \App\Models\Sekolah::factory(),
    ];
  }
}
