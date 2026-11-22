<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cermin read-only rapbs_periode ARKAS — jadwal satu baris
 * kertas kerja per periode (id_periode 81–92 = bulan
 * Januari–Desember, 1–4 = Triwulan). Realisasi BKU terhubung
 * ke baris ini lewat bos_kas_umum.id_rapbs_periode.
 */
class BosRapbsPeriode extends Model
{
  protected $table = 'bos_rapbs_periode';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_rapbs_arkas',
    'id_periode',
    'volume',
    'satuan',
    'harga_satuan',
    'jumlah',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'id_periode' => 'integer',
    'volume' => 'decimal:2',
    'harga_satuan' => 'decimal:2',
    'jumlah' => 'decimal:2',
    'soft_delete' => 'boolean',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function rapbs(): BelongsTo
  {
    return $this->belongsTo(BosRapbs::class, 'id_rapbs_arkas', 'id_arkas')
      ->where('bos_rapbs.sekolah_id', $this->sekolah_id);
  }

  public function periode(): BelongsTo
  {
    return $this->belongsTo(BosRefPeriode::class, 'id_periode', 'id_periode');
  }
}
