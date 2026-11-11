<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPelajaran extends Model
{
  protected $table = 'tabel_jadwal';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'rombel_id',
    'pengampu_id',
    'nama_kegiatan',
    'pembina_id',
    'hari',
    'jam_ke',
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

  public function pengampu(): BelongsTo
  {
    return $this->belongsTo(Pengampu::class, 'pengampu_id');
  }

  /** Pembina bila kotak berisi kegiatan (bukan pelajaran). */
  public function pembina(): BelongsTo
  {
    return $this->belongsTo(Ptk::class, 'pembina_id');
  }
}
