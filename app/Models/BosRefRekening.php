<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only ref_rekening ARKAS (kode → nama rekening). */
class BosRefRekening extends Model
{
  protected $table = 'bos_ref_rekening';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'kode_rekening',
    'nama_rekening',
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
