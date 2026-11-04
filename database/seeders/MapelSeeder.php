<?php

namespace Database\Seeders;

use App\Models\Kurikulum;
use App\Models\MapelJenjang;
use App\Models\MapelKelompok;
use App\Models\MapelMaster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MapelSeeder extends Seeder
{
  /** @var \Illuminate\Support\Collection<string,int> kode kurikulum => id */
  private $kurikulumIndex;

  public function run(): void
  {
    $this->call(KurikulumSeeder::class);

    $this->kurikulumIndex = Kurikulum::pluck('id', 'kode');

    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    DB::table('tabel_mapel_mapping')->truncate();
    MapelJenjang::truncate();
    DB::table('tabel_mapel_kurikulum')->truncate();
    MapelMaster::truncate();
    MapelKelompok::truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');

    $kelompok = $this->seedKelompok();
    $this->seedMasterMapel($kelompok);
  }

  private function seedKelompok(): array
  {
    $rows = [
      ['kode' => 'UMUM',        'nama' => 'Muatan Nasional / Umum',                    'jenjang_scope' => null,  'urutan' => 1],
      ['kode' => 'KEWILAYAHAN', 'nama' => 'Muatan Kewilayahan / Lokal',                'jenjang_scope' => null,  'urutan' => 2],
      ['kode' => 'PEMINATAN',   'nama' => 'Peminatan',                                 'jenjang_scope' => 'SMA', 'urutan' => 3],
      ['kode' => 'KEJURUAN',    'nama' => 'Muatan Kejuruan',                           'jenjang_scope' => 'SMK', 'urutan' => 4],
    ];

    $map = [];
    foreach ($rows as $row) {
      $map[$row['kode']] = MapelKelompok::updateOrCreate(['kode' => $row['kode']], $row)->id;
    }

    return $map;
  }

  private function seedMasterMapel(array $kelompok): void
  {
    $sd  = ['SD' => [1, 6]];
    $smp = ['SMP' => [7, 9]];
    $sma = ['SMA' => [10, 12]];
    $smk = ['SMK' => [10, 13]];

    $data = [

      // ── Pendidikan Agama & Budi Pekerti (semua jenjang, semua kurikulum) ──
      ['kode' => 'AGM',     'nama' => 'Pendidikan Agama dan Budi Pekerti',     'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],

      // ── Kewarganegaraan ──
      ['kode' => 'PPKN', 'nama' => 'Pendidikan Pancasila', 'nama_singkat' => 'PPKn', 'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],

      // ── Bahasa & Matematika ──
      ['kode' => 'BIN',  'nama' => 'Bahasa Indonesia', 'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],
      ['kode' => 'BING', 'nama' => 'Bahasa Inggris',   'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],
      ['kode' => 'MTK',  'nama' => 'Matematika',       'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],

      // ── IPA / IPS ──
      // Merdeka: SD gabung jadi IPAS. K13/KTSP: IPA & IPS terpisah mulai kelas 3.
      ['kode' => 'IPAS',   'nama' => 'Ilmu Pengetahuan Alam dan Sosial', 'kelompok' => 'UMUM', 'kurikulum' => ['MERDEKA'], 'jenjang' => $sd],
      ['kode' => 'IPA-SD', 'nama' => 'Ilmu Pengetahuan Alam',            'kelompok' => 'UMUM', 'kurikulum' => ['KTSP', 'K13', 'K13-REV'], 'jenjang' => ['SD' => [3, 6]]],
      ['kode' => 'IPS-SD', 'nama' => 'Ilmu Pengetahuan Sosial',          'kelompok' => 'UMUM', 'kurikulum' => ['KTSP', 'K13', 'K13-REV'], 'jenjang' => ['SD' => [3, 6]]],
      ['kode' => 'IPA',    'nama' => 'Ilmu Pengetahuan Alam',            'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => $smp],
      ['kode' => 'IPS',    'nama' => 'Ilmu Pengetahuan Sosial',          'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => $smp],

      // ── Seni, Olahraga, Informatika ──
      ['kode' => 'PJOK',        'nama' => 'Pendidikan Jasmani, Olahraga, dan Kesehatan', 'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],
      ['kode' => 'SENI',        'nama' => 'Seni Budaya',    'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sd, ...$smp, ...$sma, ...$smk]],
      ['kode' => 'PRAKARYA',    'nama' => 'Prakarya',       'kelompok' => 'UMUM', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $smp],
      ['kode' => 'INFORMATIKA', 'nama' => 'Informatika',    'kelompok' => 'UMUM', 'kurikulum' => ['MERDEKA'], 'jenjang' => [...$smp, ...$sma, ...$smk]],

      // ── Sejarah (SMA/SMK) ──
      ['kode' => 'SEJARAH', 'nama' => 'Sejarah Indonesia', 'kelompok' => 'UMUM', 'kurikulum' => null, 'jenjang' => [...$sma, 'SMK' => [10, 12]]],

      // ── Peminatan SMA (khusus K13 / K13-REV, MTK Lanjut juga ada di Merdeka) ──
      ['kode' => 'FIS',          'nama' => 'Fisika',                       'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'KIM',          'nama' => 'Kimia',                        'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'BIO',          'nama' => 'Biologi',                      'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'MTK-LANJUT',   'nama' => 'Matematika Tingkat Lanjut',    'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV', 'MERDEKA'], 'jenjang' => $sma],
      ['kode' => 'GEO',          'nama' => 'Geografi',                     'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'EKO',          'nama' => 'Ekonomi',                      'kelompok' => 'PEMINATAN', 'kurikulum' => ['KTSP', 'K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'SOS',          'nama' => 'Sosiologi',                    'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'SASINDO',      'nama' => 'Bahasa dan Sastra Indonesia',  'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'SASING',       'nama' => 'Bahasa dan Sastra Inggris',    'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],
      ['kode' => 'ANTRO',        'nama' => 'Antropologi',                  'kelompok' => 'PEMINATAN', 'kurikulum' => ['K13', 'K13-REV'], 'jenjang' => $sma],

      // ── Kejuruan SMK (mapel umum lintas jurusan) ──
      // Mapel konsentrasi keahlian spesifik per jurusan (Akuntansi, TKR, Tata Boga,
      // dll) sengaja TIDAK di-seed di sini — jumlahnya sangat banyak dan berbeda per
      // sekolah/jurusan. Sebaiknya diinput per sekolah lewat jenis='tambahan' + sekolah_id.
      ['kode' => 'DASKEJ', 'nama' => 'Dasar-dasar Program Keahlian',      'kelompok' => 'KEJURUAN', 'kurikulum' => ['MERDEKA'], 'jenjang' => ['SMK' => [10, 10]]],
      ['kode' => 'PKL',    'nama' => 'Praktik Kerja Lapangan',            'kelompok' => 'KEJURUAN', 'kurikulum' => ['K13', 'K13-REV', 'MERDEKA'], 'jenjang' => ['SMK' => [11, 13]]],
      ['kode' => 'PKK',    'nama' => 'Projek Kreatif dan Kewirausahaan',  'kelompok' => 'KEJURUAN', 'kurikulum' => ['MERDEKA'], 'jenjang' => ['SMK' => [11, 12]]],
    ];

    foreach ($data as $row) {
      /** @var MapelMaster $mapel */
      $mapel = MapelMaster::updateOrCreate(
        ['kode' => $row['kode']],
        [
          'mapel_kelompok_id' => $kelompok[$row['kelompok']],
          'nama'              => $row['nama'],
          'nama_singkat'      => $row['nama_singkat'] ?? null,
          'is_aktif'          => true,
        ]
      );

      $this->attachKurikulum($mapel->id, $row['kurikulum']);

      foreach ($row['jenjang'] as $jenjang => [$mulai, $selesai]) {
        MapelJenjang::updateOrCreate(
          ['mapel_master_id' => $mapel->id, 'jenjang' => $jenjang],
          ['kelas_mulai' => $mulai, 'kelas_selesai' => $selesai]
        );
      }
    }
  }

  /**
   * @param  array<string>|null  $kurikulumKodes  null = berlaku untuk semua kurikulum
   */
  private function attachKurikulum(int $mapelMasterId, ?array $kurikulumKodes): void
  {
    DB::table('tabel_mapel_kurikulum')->where('mapel_master_id', $mapelMasterId)->delete();

    if ($kurikulumKodes === null) {
      DB::table('tabel_mapel_kurikulum')->insert([
        'mapel_master_id' => $mapelMasterId,
        'kurikulum_id'    => null,
      ]);
      return;
    }

    foreach ($kurikulumKodes as $kode) {
      $kurikulumId = $this->kurikulumIndex[$kode] ?? null;
      if ($kurikulumId) {
        DB::table('tabel_mapel_kurikulum')->insert([
          'mapel_master_id' => $mapelMasterId,
          'kurikulum_id'    => $kurikulumId,
        ]);
      }
    }
  }
}
