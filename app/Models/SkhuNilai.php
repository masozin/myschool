<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkhuNilai extends Model
{
  protected $table    = 'tabel_skhu_nilai';
  protected $fillable = ['skhu_id', 'mapel_mapping_id', 'nilai'];

  protected $casts = ['nilai' => 'float'];

  public function skhu(): BelongsTo
  {
    return $this->belongsTo(Skhu::class);
  }

  /**
   * Relasi ke MapelMapping (menggantikan relasi Mapel lama).
   * Dari sini bisa diakses: ->mapelMapping->masterMapel->kelompok
   */
  public function mapelMapping(): BelongsTo
  {
    return $this->belongsTo(MapelMapping::class, 'mapel_mapping_id');
  }
}
