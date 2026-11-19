<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only tabel anggaran ARKAS (+ nama sumber dana). */
class BosAnggaran extends Model
{
  protected $table = 'bos_anggaran';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_ref_sumber_dana',
    'nama_sumber_dana',
    'tahun_anggaran',
    'volume',
    'harga_satuan',
    'jumlah',
    'sisa_anggaran',
    'is_pengesahan',
    'is_approve',
    'is_aktif',
    'tanggal_pengajuan',
    'tanggal_pengesahan',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'volume' => 'decimal:2',
    'harga_satuan' => 'decimal:2',
    'jumlah' => 'decimal:2',
    'sisa_anggaran' => 'decimal:2',
    'is_pengesahan' => 'boolean',
    'is_aktif' => 'boolean',
    'soft_delete' => 'boolean',
    'tanggal_pengajuan' => 'datetime',
    'tanggal_pengesahan' => 'datetime',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
