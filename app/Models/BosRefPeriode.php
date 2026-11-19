<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only ref_periode ARKAS (nama periode: bulan & triwulan). */
class BosRefPeriode extends Model
{
  protected $table = 'bos_ref_periode';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_periode',
    'periode',
    'arkas_last_update',
  ];

  protected $casts = [
    'id_periode' => 'integer',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
