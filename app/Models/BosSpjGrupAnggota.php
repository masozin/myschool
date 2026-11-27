<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris belanja BKU (bos_kas_umum) sebagai anggota
 * satu grup manual SPJ (BosSpjGrupManual). Satu baris
 * hanya boleh menjadi anggota SATU grup manual (unik
 * bos_kas_umum_id) — keanggotaan grup manual menimpa
 * pengelompokan bawaan ARKAS (per nota / nomor bukti)
 * untuk keperluan SPJ, tanpa mengubah data cermin
 * ARKAS itu sendiri.
 */
class BosSpjGrupAnggota extends Model
{
  protected $table = 'bos_spj_grup_anggota';

  protected $fillable = [
    'grup_manual_id',
    'sekolah_id',
    'bos_kas_umum_id',
  ];

  public function grupManual(): BelongsTo
  {
    return $this->belongsTo(BosSpjGrupManual::class, 'grup_manual_id');
  }

  public function baris(): BelongsTo
  {
    return $this->belongsTo(BosKasUmum::class, 'bos_kas_umum_id');
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
