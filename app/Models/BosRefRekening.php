<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cermin read-only GLOBAL (satu salinan semua sekolah) — ref_rekening ARKAS (kode → nama rekening). */
class BosRefRekening extends Model
{
  protected $table = 'bos_ref_rekening';

  protected $fillable = [
    'id_arkas',
    'kode_rekening',
    'nama_rekening',
    'tahun',
    'arkas_last_update',
  ];

  protected $casts = [
    'arkas_last_update' => 'datetime',
  ];

}
