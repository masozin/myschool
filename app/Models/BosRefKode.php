<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only ref_kode ARKAS (kegiatan). */
class BosRefKode extends Model
{
  protected $table = 'bos_ref_kode';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_ref_kode',
    'id_kode',
    'uraian_kode',
    'id_level_kode',
    'sumber_dana_id',
    'bentuk_pendidikan_id',
    'tahun',
    'arkas_last_update',
  ];

  protected $casts = [
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
