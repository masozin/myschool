<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Meta satu grup transaksi BKU untuk SPJ (Fase 2
 * lanjutan): status penyelesaian grup ('belum' /
 * 'lunas' / 'selesai') dan nomor virtual account
 * pembayaran SIPLah (isian manual — tidak ada di data
 * ARKAS). Grup diidentifikasi teks grup_kunci, kunci
 * yang sama dengan kwitansi Fase 1 dan penerima
 * Fase 2 di KelolaBosController ("nota:{id}" /
 * "bukti:{no}|{tgl}" / "baris:{id}"). Baris meta dibuat
 * malas (updateOrCreate) hanya bila pengguna mengisi
 * status / VA; ketiadaan baris berarti status 'belum'
 * dan VA kosong.
 */
class BosSpjGrup extends Model
{
  protected $table = 'bos_spj_grup';

  /** Nilai status yang sah (saklar keterangan grup). */
  public const STATUS = ['belum', 'lunas', 'selesai'];

  /** Label tampilan per nilai status. */
  public const STATUS_LABEL = [
    'belum' => 'Belum',
    'lunas' => 'Lunas',
    'selesai' => 'Selesai',
  ];

  protected $fillable = [
    'sekolah_id',
    'grup_kunci',
    'status',
    'nomor_va',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }
}
