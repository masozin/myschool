<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only tabel kas_umum_nota ARKAS. */
class BosKasNota extends Model
{
  protected $table = 'bos_kas_nota';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'no_bukti',
    'tanggal_nota',
    'no_nota',
    'nama_toko',
    'alamat_toko',
    'no_telp',
    'is_badan_usaha',
    'npwp',
    'total',
    'kode_rekening',
    'id_ref_kode',
    'has_ppn',
    'has_pph_22',
    'alasan_pecah_nota',
    'is_beli_di_siplah',
    'details',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'tanggal_nota' => 'date',
    'total' => 'decimal:2',
    'is_badan_usaha' => 'boolean',
    'has_ppn' => 'boolean',
    'has_pph_22' => 'boolean',
    'is_beli_di_siplah' => 'boolean',
    'soft_delete' => 'boolean',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
