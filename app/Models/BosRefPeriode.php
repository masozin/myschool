<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cermin read-only GLOBAL (satu salinan semua sekolah) — ref_periode ARKAS (nama periode: bulan & triwulan). */
class BosRefPeriode extends Model
{
  protected $table = 'bos_ref_periode';

  protected $fillable = [
    'id_arkas',
    'id_periode',
    'periode',
    'arkas_last_update',
  ];

  protected $casts = [
    'id_periode' => 'integer',
    'arkas_last_update' => 'datetime',
  ];

}
