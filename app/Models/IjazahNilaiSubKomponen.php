<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Nilai aktual per sub-komponen per siswa per mapel.
 *
 * @property int        $id
 * @property int        $ijazah_nilai_komponen_id
 * @property int        $sub_komponen_id
 * @property float|null $nilai
 */
class IjazahNilaiSubKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_ijazah_nilai_sub_komponen';
  protected $fillable = ['ijazah_nilai_komponen_id', 'sub_komponen_id', 'nilai'];

  protected $casts = ['nilai' => 'decimal:2'];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function ijazahNilaiKomponen(): BelongsTo
  {
    return $this->belongsTo(IjazahNilaiKomponen::class, 'ijazah_nilai_komponen_id');
  }

  public function subKomponen(): BelongsTo
  {
    return $this->belongsTo(IjazahSubKomponen::class, 'sub_komponen_id');
  }
}
