<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Cermin read-only tabel rapbs ARKAS (baris Kertas Kerja). */
class BosRapbs extends Model
{
  protected $table = 'bos_rapbs';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_anggaran_arkas',
    'id_ref_kode',
    'id_ref_tahun_anggaran',
    'kode_rekening',
    'id_barang',
    'urutan',
    'uraian',
    'uraian_text',
    'volume',
    'satuan',
    'harga_satuan',
    'jumlah',
    'v1',
    's1',
    'v2',
    's2',
    'v3',
    's3',
    'v4',
    's4',
    'keterangan',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'volume' => 'decimal:2',
    'harga_satuan' => 'decimal:2',
    'jumlah' => 'decimal:2',
    'v1' => 'decimal:2',
    'v2' => 'decimal:2',
    'v3' => 'decimal:2',
    'v4' => 'decimal:2',
    'soft_delete' => 'boolean',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function anggaran(): BelongsTo
  {
    return $this->belongsTo(BosAnggaran::class, 'id_anggaran_arkas', 'id_arkas')
      ->where('bos_anggaran.sekolah_id', $this->sekolah_id);
  }

  public function kegiatan(): BelongsTo
  {
    return $this->belongsTo(BosRefKode::class, 'id_ref_kode', 'id_arkas')
      ->where('bos_ref_kode.sekolah_id', $this->sekolah_id);
  }
}
