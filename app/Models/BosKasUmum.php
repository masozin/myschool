<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cermin read-only tabel kas_umum ARKAS (BKU). Diisi agen
 * sinkron; jangan diubah dari aplikasi web.
 */
class BosKasUmum extends Model
{
  protected $table = 'bos_kas_umum';

  protected $fillable = [
    'sekolah_id',
    'id_arkas',
    'id_kas_nota',
    'id_rapbs_periode',
    'id_anggaran_arkas',
    'kode_rekening',
    'id_ref_bku',
    'tanggal_transaksi',
    'no_bukti',
    'uraian',
    'uraian_pajak',
    'saldo',
    'status_bku',
    'volume',
    'is_ppn',
    'is_pph_21',
    'is_pph_22',
    'is_pph_23',
    'is_pph_4',
    'is_sspd',
    'is_lock',
    'soft_delete',
    'arkas_last_update',
  ];

  protected $casts = [
    'tanggal_transaksi' => 'datetime',
    'saldo' => 'decimal:2',
    'volume' => 'decimal:2',
    'is_ppn' => 'boolean',
    'is_pph_21' => 'boolean',
    'is_pph_22' => 'boolean',
    'is_pph_23' => 'boolean',
    'is_pph_4' => 'boolean',
    'is_sspd' => 'boolean',
    'is_lock' => 'boolean',
    'soft_delete' => 'boolean',
    'arkas_last_update' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function jenisBku(): BelongsTo
  {
    return $this->belongsTo(BosRefBku::class, 'id_ref_bku', 'id_ref_bku');
  }

  public function nota(): BelongsTo
  {
    return $this->belongsTo(BosKasNota::class, 'id_kas_nota', 'id_arkas')
      ->where('bos_kas_nota.sekolah_id', $this->sekolah_id);
  }

  /** Label pajak yang melekat pada transaksi, mis. "PPN, PPh 21". */
  public function labelPajak(): string
  {
    $label = [];
    if ($this->is_ppn) $label[] = 'PPN';
    if ($this->is_pph_21) $label[] = 'PPh 21';
    if ($this->is_pph_22) $label[] = 'PPh 22';
    if ($this->is_pph_23) $label[] = 'PPh 23';
    if ($this->is_pph_4) $label[] = 'PPh 4(2)';
    if ($this->is_sspd) $label[] = 'SSPD';

    return implode(', ', $label);
  }
}
