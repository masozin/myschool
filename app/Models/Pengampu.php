<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pengampu extends Model
{
  protected $table = 'tabel_pengampu';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'rombel_id',
    'mapel_mapping_id',
    'ptk_id',
    'target_jam',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }

  public function rombel(): BelongsTo
  {
    return $this->belongsTo(Rombel::class, 'rombel_id');
  }

  public function mapping(): BelongsTo
  {
    return $this->belongsTo(MapelMapping::class, 'mapel_mapping_id');
  }

  public function ptk(): BelongsTo
  {
    return $this->belongsTo(Ptk::class, 'ptk_id');
  }

  public function jadwal(): HasMany
  {
    return $this->hasMany(JadwalPelajaran::class, 'pengampu_id');
  }
}
