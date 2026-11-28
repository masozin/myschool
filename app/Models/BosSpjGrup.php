<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meta satu grup transaksi BKU untuk SPJ (Fase 2
 * lanjutan): status penyelesaian grup ('belum' /
 * 'selesai'), nomor virtual account pembayaran
 * SIPLah (isian manual — tidak ada di data ARKAS),
 * dan kategori: override manual atas deteksi otomatis
 * dari kode rekening. Nilai sah 'barang' |
 * 'barang_konsumsi' | 'non_barang' (non-barang =
 * pembayaran ke orang: honor, transport, insentif —
 * sasaran Tanda Terima); kategori null berarti ikut
 * deteksi otomatis. Grup
 * diidentifikasi teks grup_kunci, kunci yang sama
 * dengan kwitansi Fase 1 dan penerima Fase 2 di
 * KelolaBosController ("nota:{id}" / "bukti:{no}|{tgl}"
 * / "baris:{id}"). Baris meta dibuat malas
 * (updateOrCreate) hanya bila pengguna mengisi
 * status / VA / kategori; ketiadaan baris berarti
 * status 'belum', VA kosong, kategori otomatis.
 */
class BosSpjGrup extends Model
{
  protected $table = 'bos_spj_grup';

  /** Nilai status yang sah (saklar keterangan grup). */
  public const STATUS = ['belum', 'selesai'];

  /** Label tampilan per nilai status. */
  public const STATUS_LABEL = [
    'belum' => 'Belum',
    'selesai' => 'Selesai',
  ];

  protected $fillable = [
    'sekolah_id',
    'grup_kunci',
    'status',
    'nama',
    'nomor_va',
    'non_barang',
    'kategori',
  ];

  protected $casts = [
    'non_barang' => 'boolean',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
