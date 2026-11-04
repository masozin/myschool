<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelKelompok extends Model
{
  protected $table = 'tabel_mapel_kelompok';

  protected $fillable = ['kode', 'nama', 'keterangan', 'jenjang_scope', 'urutan'];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

  /** Master mapel yang termasuk kelompok ini */
  public function masterMapel(): HasMany
  {
    return $this->hasMany(MapelMaster::class, 'mapel_kelompok_id');
  }
}
