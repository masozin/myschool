<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cermin read-only GLOBAL (satu salinan semua sekolah) — ref_satuan ARKAS. */
class BosRefSatuan extends Model
{
  protected $table = 'bos_ref_satuan';

  protected $fillable = [
    'id_arkas',
    'satuan',
    'unit',
  ];

}
