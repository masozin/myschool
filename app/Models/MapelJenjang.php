<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MapelJenjang extends Model
{
  protected $table = 'tabel_mapel_jenjang';

  public $timestamps = false;

  protected $fillable = [
    'mapel_master_id',
    'jenjang',
    'kelas_mulai',
    'kelas_selesai',
  ];

  protected $casts = [
    'kelas_mulai'   => 'integer',
    'kelas_selesai' => 'integer',
  ];

  public function mapelMaster(): BelongsTo
  {
    return $this->belongsTo(MapelMaster::class);
  }
}
