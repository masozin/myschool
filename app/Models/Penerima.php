<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Induk penerima SPJ non-PTK (tabel_penerima) — penerima
 * pembayaran BOS di luar daftar PTK. Dua jenis: 'orang'
 * (narasumber luar, pelatih ekstrakurikuler, komite,
 * dan sebagainya; rincian NIK / NIP / pangkat) dan
 * 'toko' (toko / usaha langganan; rincian pemilik,
 * NPWP, no HP). Dikelola dari modal Daftar Penerima di
 * halaman BKU; saat penerima ditambahkan ke sebuah
 * transaksi, datanya DISALIN menjadi snapshot di
 * bos_spj_penerima, jadi perubahan di induk tidak
 * mengubah SPJ lama.
 */
class Penerima extends Model
{
  protected $table = 'tabel_penerima';

  protected $fillable = [
    'sekolah_id',
    'nama',
    'jenis',
    'nik',
    'nip',
    'pangkat_gol',
    'pemilik',
    'npwp',
    'no_hp',
    'alamat',
    'no_rekening',
    'nama_bank',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
