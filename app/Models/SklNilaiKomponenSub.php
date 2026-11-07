<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Nilai per sub-komponen (dulu SklNilaiSubKomponen /
 * tabel_skl_nilai_sub_komponen; diganti nama mengikuti pola
 * induk-dulu: skl_nilai_komponen_sub).
 */
class SklNilaiKomponenSub extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_nilai_komponen_sub';
  protected $fillable = ['skl_nilai_komponen_id', 'komponen_sub_id', 'nilai'];

  protected $casts = ['nilai' => 'decimal:2'];

  public function sklNilaiKomponen(): BelongsTo
  {
    return $this->belongsTo(SklNilaiKomponen::class, 'skl_nilai_komponen_id');
  }

  public function komponenSub(): BelongsTo
  {
    return $this->belongsTo(SklKomponenSub::class, 'komponen_sub_id');
  }
}
