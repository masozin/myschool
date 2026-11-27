<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu penerima pada satu grup transaksi BKU (SPJ Fase 2).
 * Baris ini adalah SNAPSHOT data penerima saat ditambahkan
 * — dari PTK (ptk_id terisi), dari induk penerima
 * (penerima_id terisi), atau ketikan manual (keduanya
 * kosong). Nilai pembayaran per orang: jumlah = volume ×
 * satuan; pph = potongan PPh dari jumlah menurut
 * pph_persen; diterima = jumlah − pph.
 *
 * Grup diidentifikasi teks grup_kunci — kunci yang sama
 * dengan pengelompokan kwitansi Fase 1 di
 * KelolaBosController ("nota:{id}" / "bukti:{no}|{tgl}" /
 * "baris:{id}").
 *
 * Kolom selesai adalah penanda tuntas per penerima:
 * status tampil grup yang punya penerima diturunkan
 * dari penanda ini (semua selesai -> selesai, sebagian
 * -> selesai sebagian, belum ada -> belum); lihat
 * KelolaBosController::statusTampilGrup.
 */
class BosSpjPenerima extends Model
{
  protected $table = 'bos_spj_penerima';

  protected $fillable = [
    'sekolah_id',
    'grup_kunci',
    'ptk_id',
    'penerima_id',
    'nama',
    'nip',
    'pangkat_gol',
    'nik',
    'no_rekening',
    'nama_bank',
    'volume',
    'satuan',
    'pph_persen',
    'urutan',
    'selesai',
  ];

  protected $casts = [
    'volume' => 'decimal:2',
    'satuan' => 'decimal:2',
    'pph_persen' => 'decimal:2',
    'selesai' => 'boolean',
  ];

  /** Jumlah kotor penerima ini: volume × satuan (2 desimal). */
  public function getJumlahAttribute(): float
  {
    return round((float) $this->volume * (float) $this->satuan, 2);
  }

  /** Potongan PPh dalam rupiah bulat: jumlah × pph_persen / 100. */
  public function getPphAttribute(): float
  {
    return round($this->jumlah * (float) $this->pph_persen / 100, 0);
  }

  /** Yang benar-benar diterima: jumlah − pph. */
  public function getDiterimaAttribute(): float
  {
    return $this->jumlah - $this->pph;
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function ptk(): BelongsTo
  {
    return $this->belongsTo(Ptk::class, 'ptk_id');
  }

  public function penerimaInduk(): BelongsTo
  {
    return $this->belongsTo(Penerima::class, 'penerima_id');
  }
}
