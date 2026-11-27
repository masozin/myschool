<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Grup manual SPJ: pengelompokan baris belanja BKU yang
 * dibuat pengguna dengan menggabungkan beberapa nota /
 * nomor bukti (gabung) atau memecah satu nota menjadi
 * beberapa bagian (pecah). Tiap grup punya nama alias
 * yang tampil di tabel BKU dan menjadi uraian
 * pembayaran pada kwitansi grup itu.
 *
 * Ini LAPISAN SPJ murni — data cermin ARKAS
 * (bos_kas_umum / bos_kas_nota) tidak pernah diubah.
 * Keanggotaan disimpan di bos_spj_grup_anggota; kunci
 * grupnya "manual:{id}" mengikuti pola kunci grup
 * kwitansi lain di KelolaBosController ("nota:{id}" /
 * "bukti:{no}|{tgl}" / "baris:{id}") sehingga penerima,
 * meta, dan kwitansi menempel dengan cara yang sama.
 */
class BosSpjGrupManual extends Model
{
  protected $table = 'bos_spj_grup_manual';

  protected $fillable = [
    'sekolah_id',
    'nama',
  ];

  /** Kunci grup kwitansi untuk grup manual ini. */
  public function kunci(): string
  {
    return 'manual:' . $this->id;
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function anggota(): HasMany
  {
    return $this->hasMany(BosSpjGrupAnggota::class, 'grup_manual_id');
  }
}
