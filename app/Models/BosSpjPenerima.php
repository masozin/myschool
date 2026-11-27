<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu penerima pada satu grup transaksi BKU (SPJ Fase 2).
 * Baris ini adalah SNAPSHOT data penerima saat ditambahkan
 * — dari PTK (ptk_id terisi), dari induk penerima
 * (penerima_id terisi), atau ketikan manual (keduanya
 * kosong). Kolom jenis membedakan penerima: 'orang'
 * (perorangan) atau 'toko' (toko/usaha, mis. nama toko
 * pada nota — tanpa data perorangan seperti NIP,
 * pangkat, dan NIK). Nilai pembayaran per penerima: jumlah = volume ×
 * satuan; potongannya kini nominal RUPIAH tersimpan per
 * komponen (ppn_rp, pph_rp, sspd_rp, admin_rp — isian
 * bawaan dari pajak nota di BKU, dapat diubah manual);
 * diterima = jumlah − ppn − pph − sspd − biaya admin.
 * Kolom pph_persen lama tidak dipakai lagi (potongan
 * persen sebelum migrasi 2026_11_27_000005 yang mengisi
 * pph_rp dari persen itu).
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
    'jenis',
    'nama',
    'nip',
    'pangkat_gol',
    'nik',
    'no_rekening',
    'nama_bank',
    'volume',
    'satuan',
    'pph_persen',
    'ppn_rp',
    'pph_rp',
    'sspd_rp',
    'admin_rp',
    'urutan',
    'selesai',
  ];

  protected $casts = [
    'volume' => 'decimal:2',
    'satuan' => 'decimal:2',
    'pph_persen' => 'decimal:2',
    'ppn_rp' => 'decimal:2',
    'pph_rp' => 'decimal:2',
    'sspd_rp' => 'decimal:2',
    'admin_rp' => 'decimal:2',
    'selesai' => 'boolean',
  ];

  /** Jumlah kotor penerima ini: volume × satuan (2 desimal). */
  public function getJumlahAttribute(): float
  {
    return round((float) $this->volume * (float) $this->satuan, 2);
  }

  /** Potongan PPN dalam rupiah (kolom ppn_rp tersimpan). */
  public function getPpnAttribute(): float
  {
    return (float) ($this->ppn_rp ?? 0);
  }

  /** Potongan PPh dalam rupiah (kolom pph_rp tersimpan). */
  public function getPphAttribute(): float
  {
    return (float) ($this->pph_rp ?? 0);
  }

  /** Potongan SSPD dalam rupiah (kolom sspd_rp tersimpan). */
  public function getSspdAttribute(): float
  {
    return (float) ($this->sspd_rp ?? 0);
  }

  /** Biaya admin dalam rupiah (kolom admin_rp tersimpan). */
  public function getBiayaAdminAttribute(): float
  {
    return (float) ($this->admin_rp ?? 0);
  }

  /** Yang benar-benar diterima: jumlah − semua potongan. */
  public function getDiterimaAttribute(): float
  {
    return $this->jumlah - $this->ppn - $this->pph - $this->sspd - $this->biaya_admin;
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
