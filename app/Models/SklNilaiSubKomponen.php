<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SklNilaiSubKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_nilai_sub_komponen';
  protected $fillable = ['skl_nilai_komponen_id', 'sub_komponen_id', 'nilai'];

  protected $casts = ['nilai' => 'decimal:2'];

  public function sklNilaiKomponen(): BelongsTo
  {
    return $this->belongsTo(SklNilaiKomponen::class, 'skl_nilai_komponen_id');
  }

  public function subKomponen(): BelongsTo
  {
    return $this->belongsTo(SklSubKomponen::class, 'sub_komponen_id');
  }
}
