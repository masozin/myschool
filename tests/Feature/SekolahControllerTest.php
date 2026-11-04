<?php

namespace Tests\Feature;

use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use App\Models\User;
use App\Models\Sekolah;
use App\Models\SekolahDetail;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SekolahControllerTest extends TestCase
{
  use RefreshDatabase;

  // Fungsi pembantu agar tidak menulis ulang data wilayah di setiap test
  private function setupWilayah()
  {
    DB::table('ref_provinces')->insert(['id' => '12', 'name' => 'SUMATERA UTARA']);
    DB::table('ref_regencies')->insert(['id' => '1201', 'province_id' => '12', 'name' => 'TAPANULI']);
    DB::table('ref_districts')->insert(['id' => '120101', 'regency_id' => '1201', 'name' => 'BARUS']);
    DB::table('ref_villages')->insert(['id' => '12010101', 'district_id' => '120101', 'name' => 'KAMPUNG']);
  }

  #[Test]
  public function data_sekolah_berhasil_disimpan_dan_membuat_detail()
  {
    // 1. Setup Data
    Semester::create(['kode_semester' => '1', 'nama_semester' => 'Gasal', 'tahun_pelajaran' => '2025', 'is_active' => true]);
    $this->setupWilayah();

    $user = User::factory()->create();
    session(['semester_id' => '1']);

    $data = [
      'npsn'           => '12345678',
      'jenjang'        => 'SD',
      'nama_sekolah'   => 'SD Test Unit',
      'province'       => '12',
      'regency'        => '1201',
      'district'       => '120101',
      'village'        => '12010101',
      'alamat_sekolah' => 'Jl. Test No. 1',
      'kepala_sekolah' => 'Budi Sudarsono',
      'nip'            => '198801012010121001'
    ];

    // 2. Action
    $response = $this->actingAs($user)->postJson('/sekolah/store', $data);

    // 3. Assert
    $response->assertStatus(201);
    $this->assertDatabaseHas('tabel_sekolah', ['npsn' => '12345678']);
    $this->assertDatabaseHas('tabel_sekolah_detail', ['nama_sekolah' => 'SD Test Unit']);
  }

  #[Test]
  public function update_sekolah_di_semester_berbeda_akan_membuat_record_detail_baru()
  {
    // 1. Setup
    Semester::create(['kode_semester' => '1', 'nama_semester' => 'S1', 'tahun_pelajaran' => '24', 'is_active' => false]);
    Semester::create(['kode_semester' => '2', 'nama_semester' => 'S2', 'tahun_pelajaran' => '24', 'is_active' => true]);
    $this->setupWilayah();

    $sekolah = Sekolah::create(['npsn' => '88888888', 'jenjang' => 'SMP']);
    $sekolah->sekolahDetail()->create([
      'semester_id'    => '1',
      'nama_sekolah'   => 'SMP Lama',
      'kepala_sekolah' => 'Kepala Lama',
      'province_id'    => '12',
      'regency_id'     => '1201',
      'district_id'    => '120101',
      'village_id'     => '12010101',
    ]);

    session(['semester_id' => '2']);
    $user = User::factory()->create();

    $updateData = [
      'npsn'           => '88888888',
      'jenjang'        => 'SMP',
      'nama_sekolah'   => 'SMP Nama Baru',
      'alamat_sekolah' => 'Alamat Baru',
      'kepala_sekolah' => 'Kepala Baru',
      'province'       => '12',
      'regency'        => '1201',
      'district'       => '120101',
      'village'        => '12010101'
    ];

    // 2. Action
    $response = $this->actingAs($user)->postJson("/sekolah/update/{$sekolah->id}", $updateData);

    // 3. Assert
    $response->assertStatus(200);
    $this->assertEquals(2, SekolahDetail::where('sekolah_id', $sekolah->id)->count());
  }
}
