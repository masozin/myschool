<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kurikulum extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_kurikulum';

  protected $fillable = [
    'kode',
    'nama',
    'deskripsi',
    'tahun_berlaku',
    'is_aktif',
  ];

  protected $casts = [
    'tahun_berlaku' => 'integer',
    'is_aktif'      => 'boolean',
  ];

  // -------------------------------------------------------
  // Relationships
  // -------------------------------------------------------

  public function mapelMapping(): BelongsToMany
  {
    return $this->belongsToMany(MapelMapping::class, 'mapel_kurikulum');
  }

  // -------------------------------------------------------
  // Scopes
  // -------------------------------------------------------

  public function scopeAktif($query)
  {
    return $query->where('is_aktif', true);
  }
}
