<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only ref_bku ARKAS (jenis transaksi BKU). */
class BosRefBku extends Model
{
  protected $table = 'bos_ref_bku';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_ref_bku',
    'nama_bku',
    'kode_bku',
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
