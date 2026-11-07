<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sub-komponen nilai SKL (dulu SklSubKomponen / tabel_skl_sub_komponen;
 * diganti nama mengikuti pola induk-dulu: skl_komponen_sub).
 */
class SklKomponenSub extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_komponen_sub';
  protected $fillable = ['komponen_id', 'label', 'keterangan', 'urutan'];

  public function komponen(): BelongsTo
  {
    return $this->belongsTo(SklKomponen::class, 'komponen_id');
  }

  public function nilaiKomponenSub(): HasMany
  {
    return $this->hasMany(SklNilaiKomponenSub::class, 'komponen_sub_id');
  }
}
