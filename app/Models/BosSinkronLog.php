<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris per kiriman agen sinkron (per tabel per POST). */
class BosSinkronLog extends Model
{
  protected $table = 'bos_sinkron_log';

  protected $fillable = [
    'sekolah_id',
    'tabel',
    'diterima',
    'tersimpan',
    'agen_versi',
    'catatan',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  /** Waktu sinkron terakhir sebuah sekolah (semua tabel). */
  public static function terakhirUntuk(int $sekolahId): ?self
  {
    return static::where('sekolah_id', $sekolahId)
      ->orderByDesc('created_at')
      ->orderByDesc('id')
      ->first();
  }
}
