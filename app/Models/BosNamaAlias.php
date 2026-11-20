<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Nama alias buatan pengguna (lapisan kerja Kelola BOS) —
 * nama pendek untuk nama asli ARKAS yang panjang, mis.
 * uraian "Honorarium Pelatih/Pembina Ekstrakurikuler"
 * ditampilkan sebagai "Honor Ekskul". Dipakai bersama oleh
 * Kertas Kerja, BKU, dan Referensi Barang: kuncinya teks
 * nama_sumber yang persis sama dengan nama dari ARKAS.
 * Bukan data sinkron — tidak pernah ditimpa agen ARKAS.
 */
class BosNamaAlias extends Model
{
  protected $table = 'bos_nama_alias';

  protected $fillable = [
    'sekolah_id',
    'nama_sumber',
    'alias',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
