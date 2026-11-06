<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daftar mapel pokok yang dinonaktifkan oleh sebuah sekolah.
 *
 * Baris pokok dipakai bersama oleh semua sekolah, jadi sekolah
 * tidak mengubah barisnya; sekolah mencatat pengecualiannya di
 * sini. Mapping pokok yang tercatat untuk sekolah tertentu
 * tidak berlaku bagi sekolah itu (lihat scope berlakuUntuk di
 * MapelMapping).
 */
class MapelNonaktifSekolah extends Model
{
  protected $table = 'tabel_mapel_nonaktif_sekolah';

  protected $fillable = [
    'sekolah_id',
    'mapel_mapping_id',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function mapelMapping(): BelongsTo
  {
    return $this->belongsTo(MapelMapping::class);
  }
}
