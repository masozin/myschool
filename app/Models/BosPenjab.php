<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only sekolah_penjab ARKAS (kepsek/bendahara per tahun). */
class BosPenjab extends Model
{
  protected $table = 'bos_penjab';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_penjab_arkas',
    'tahun',
    'tanggal_mulai',
    'tanggal_selesai',
    'ks',
    'nip_ks',
    'bendahara',
    'nip_bendahara',
    'komite',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'tanggal_mulai' => 'date',
    'tanggal_selesai' => 'date',
    'soft_delete' => 'boolean',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
