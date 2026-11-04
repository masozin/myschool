<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Laravolt\Indonesia\Models\Province;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Village;

class SekolahDetail extends Model
{
  use HasFactory;

  protected $table = 'tabel_sekolah_detail';

  protected $fillable = [
    'semester_id',
    'sekolah_id',
    'nama_sekolah',
    'province_id',
    'regency_id',
    'district_id',
    'village_id',
    'alamat_sekolah',
    'kepala_sekolah',
    'nip',
    'libur_mingguan',
    'no_telp',
    'email',
    'site',
  ];

  protected $casts = [
    'libur_mingguan' => 'array',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }

  public function province(): BelongsTo
  {
    return $this->belongsTo(Province::class, 'province_id', 'code');
  }

  public function regency(): BelongsTo
  {
    return $this->belongsTo(City::class, 'regency_id', 'code');
  }

  public function district(): BelongsTo
  {
    return $this->belongsTo(District::class, 'district_id', 'code');
  }

  public function village(): BelongsTo
  {
    return $this->belongsTo(Village::class, 'village_id', 'code');
  }

  public static function status(int $sekolahId, int $semesterId): array
  {
    return [
      'hasDetail' => SekolahDetail::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)->exists(),
      'hasRombel' => Rombel::where('sekolah_id', $sekolahId)
        ->where('semester_id', $semesterId)->exists(),
    ];
  }
}
