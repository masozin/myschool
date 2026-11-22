<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cermin read-only GLOBAL (satu salinan semua sekolah) — ref_bku ARKAS (jenis transaksi BKU). */
class BosRefBku extends Model
{
  protected $table = 'bos_ref_bku';

  protected $fillable = [
    'id_arkas',
    'id_ref_bku',
    'nama_bku',
    'kode_bku',
    'arkas_last_update',
  ];

  protected $casts = [
    'arkas_last_update' => 'datetime',
  ];

}
