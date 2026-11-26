<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu entri riwayat kepangkatan PTK (pangkat + golongan + TMT +
 * nomor/tanggal SK). Pangkat yang berlaku bagi seorang PTK adalah
 * entri dengan TMT terbaru — lihat Ptk::pangkatTerakhir().
 */
class PtkRiwayatPangkat extends Model
{
  protected $table = 'tabel_ptk_riwayat_pangkat';

  protected $fillable = ['ptk_id', 'pangkat', 'golongan', 'tmt', 'nomor_sk', 'tanggal_sk'];

  protected $casts = [
    'tmt' => 'date',
    'tanggal_sk' => 'date',
  ];

  public function ptk(): BelongsTo
  {
    return $this->belongsTo(Ptk::class);
  }
}
