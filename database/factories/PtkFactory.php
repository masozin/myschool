<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Guru>
 */
class PtkFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'nama_ptk' => fake()->name(),
      'nip' => fake()->unique()->numerify('##################'),
      'jenis_kelamin' => fake()->randomElement(['L', 'P']),
      'jabatan' => fake()->randomElement(['KS', 'Guru', 'Admin']),
      'sekolah_id' => \App\Models\Sekolah::factory(),
    ];
  }
}
