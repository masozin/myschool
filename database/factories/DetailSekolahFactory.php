<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SekolahDetail>
 */
class SekolahDetailFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'sekolah_id' => \App\Models\Sekolah::factory(),
      'semester_id' => \App\Models\Semester::factory(),
      'nama_sekolah' => 'SD Negeri ' . fake()->city(),
      'alamat_sekolah' => fake()->address(), // Sesuai migrasi: 'alamat'
      'kepala_sekolah' => fake()->name(),
      'nip' => fake()->numerify('################'),
    ];
  }
}
