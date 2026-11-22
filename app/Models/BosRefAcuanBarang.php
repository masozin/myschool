<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Cermin read-only GLOBAL (satu salinan semua sekolah) — ref_acuan_barang ARKAS (kode barang + harga acuan). */
class BosRefAcuanBarang extends Model
{
  protected $table = 'bos_ref_acuan_barang';

  protected $fillable = [
    'id_arkas',
    'id_barang',
    'kode_rekening',
    'nama_barang',
    'satuan',
    'blok_id',
    'kode_belanja',
    'harga_barang',
    'batas_bawah',
    'batas_atas',
    'tahun',
    'arkas_last_update',
  ];

  protected $casts = [
    'harga_barang' => 'decimal:2',
    'batas_bawah' => 'decimal:2',
    'batas_atas' => 'decimal:2',
    'arkas_last_update' => 'datetime',
  ];

}
