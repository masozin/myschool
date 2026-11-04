<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Siswa>
 */
class SiswaFactory extends Factory
{
  /**
   * Define the model's default state.
   *
   * @return array<string, mixed>
   */
  public function definition(): array
  {
    return [
      'nama_siswa' => fake()->name(),
      'nisn' => fake()->unique()->numerify('##########'),
      'jenis_kelamin' => fake()->randomElement(['L', 'P']),
      'tempat_lahir' => fake()->city(),
      'tgl_lahir' => fake()->date(),
      'nik' => fake()->unique()->numerify('################'),
      'agama' => 'Islam',
      'alamat' => fake()->address(),
      'data_orang_tua' => [
        'ayah' => fake()->name('male'),
        'ibu' => fake()->name('female'),
      ],
      'no_hp' => fake()->phoneNumber(),
      'status' => 'aktif',
    ];
  }
}
