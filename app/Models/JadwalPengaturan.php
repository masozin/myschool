<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPengaturan extends Model
{
  protected $table = 'tabel_jadwal_pengaturan';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'nama',
    'kerangka',
  ];

  protected $casts = [
    'kerangka' => 'array',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }
}
