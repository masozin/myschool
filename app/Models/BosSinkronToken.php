<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Token agen sinkron per sekolah. Yang disimpan hanya hash
 * sha256; token asli dibuat perintah `bos:token` dan hanya
 * ditampilkan sekali di sana.
 */
class BosSinkronToken extends Model
{
  protected $table = 'bos_sinkron_token';

  protected $fillable = [
    'sekolah_id',
    'token_hash',
    'terakhir_dipakai',
  ];

  protected $casts = [
    'terakhir_dipakai' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
