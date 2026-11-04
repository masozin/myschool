<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelMapping extends Model
{
  use HasFactory;

  protected $table = 'tabel_mapel_mapping';

  protected $fillable = [
    'mapel_master_id',
    'jenjang',
    'tingkat',
    'semester_id',
    'jenis', // pokok, tambahan (pokok berlku untuk semua sekolah sesui jenjang dan tingkat)
    'sekolah_id',  //untuk mapping lokal (jenis: tambahan)
    'urutan',
  ];

  protected $casts = [
    'tingkat'     => 'integer',
    'urutan'      => 'integer',
    'semester_id' => 'integer',
  ];

  // -------------------------------------------------------
  // Relationships
  // -------------------------------------------------------

  public function masterMapel(): BelongsTo
  {
    return $this->belongsTo(MapelMaster::class, 'mapel_master_id');
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function skhuNilai(): HasMany
  {
    return $this->hasMany(SkhuNilai::class, 'mapel_mapping_id');
  }
}
