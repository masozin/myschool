<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only ref_satuan ARKAS. */
class BosRefSatuan extends Model
{
  protected $table = 'bos_ref_satuan';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'satuan',
    'unit',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
