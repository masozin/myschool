<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Induk penerima SPJ non-PTK (tabel_penerima) — orang di
 * luar daftar PTK yang menerima pembayaran BOS: narasumber
 * luar, pelatih ekstrakurikuler, komite, dan sebagainya.
 * Dikelola dari editor penerima di halaman BKU; saat
 * seseorang ditambahkan ke sebuah transaksi, datanya
 * DISALIN menjadi snapshot di bos_spj_penerima, jadi
 * perubahan di induk tidak mengubah SPJ lama.
 */
class Penerima extends Model
{
  protected $table = 'tabel_penerima';

  protected $fillable = [
    'sekolah_id',
    'nama',
    'nik',
    'nip',
    'pangkat_gol',
    'alamat',
    'no_rekening',
    'nama_bank',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
