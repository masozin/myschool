<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MapelRef extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'mapel';

  protected $fillable = [
    'kode',
    'nama',
    'nama_singkat',
    'kelompok',
    'is_aktif',
  ];

  protected $casts = [
    'is_aktif' => 'boolean',
  ];

  // -------------------------------------------------------
  // Relationships
  // -------------------------------------------------------

  public function kurikulum(): BelongsToMany
  {
    return $this->belongsToMany(Kurikulum::class, 'mapel_kurikulum');
  }

  public function jenjangDetail(): HasMany
  {
    return $this->hasMany(MapelJenjang::class);
  }

  // -------------------------------------------------------
  // Scopes
  // -------------------------------------------------------

  public function scopeAktif($query)
  {
    return $query->where('is_aktif', true);
  }

  /**
   * Filter mapel berdasarkan jenjang.
   * Contoh: Mapel::forJenjang('SMA')->get()
   */
  public function scopeForJenjang($query, string $jenjang)
  {
    return $query->whereHas('jenjangDetail', fn($q) => $q->where('jenjang', $jenjang));
  }

  /**
   * Filter mapel berdasarkan kurikulum.
   * Contoh: Mapel::forKurikulum('MERDEKA')->get()
   */
  public function scopeForKurikulum($query, string|int $kurikulum)
  {
    return $query->whereHas('kurikulum', function ($q) use ($kurikulum) {
      is_int($kurikulum)
        ? $q->where('kurikulum.id', $kurikulum)
        : $q->where('kurikulum.kode', $kurikulum);
    });
  }

  /**
   * Filter gabungan kurikulum + jenjang.
   * Contoh: Mapel::forKurikulumAndJenjang('MERDEKA', 'SMA')->get()
   */
  public function scopeForKurikulumAndJenjang($query, string|int $kurikulum, string $jenjang)
  {
    return $query
      ->forKurikulum($kurikulum)
      ->forJenjang($jenjang);
  }
}
