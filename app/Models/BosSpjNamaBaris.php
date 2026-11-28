<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nama uraian BKU khusus satu baris belanja (SPJ
 * Fase 2 lanjutan): nama yang diedit dari BKU dan
 * hanya berlaku untuk baris (item) itu sendiri —
 * menggantikan tampilannya di BKU, modal isi grup,
 * dan rincian kwitansi. Berbeda dari alias teks
 * (bos_nama_alias, per teks uraian untuk semua baris
 * yang sama) dan dari nama tampilan grup
 * (bos_spj_grup.nama, per grup berisi beberapa item).
 * Melekat pada bos_kas_umum_id, jadi ikut terbawa
 * saat baris berpindah grup (gabung / pecah nota).
 */
class BosSpjNamaBaris extends Model
{
  protected $table = 'bos_spj_nama_baris';

  protected $fillable = [
    'sekolah_id',
    'bos_kas_umum_id',
    'nama',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function baris(): BelongsTo
  {
    return $this->belongsTo(BosKasUmum::class, 'bos_kas_umum_id');
  }
}
