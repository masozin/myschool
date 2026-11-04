<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SklSubKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_sub_komponen';
  protected $fillable = ['komponen_id', 'label', 'keterangan', 'urutan'];

  public function komponen(): BelongsTo
  {
    return $this->belongsTo(SklKomponen::class, 'komponen_id');
  }

  public function nilaiSubKomponen(): HasMany
  {
    return $this->hasMany(SklNilaiSubKomponen::class, 'sub_komponen_id');
  }
}
