<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Sub-komponen dari sebuah IjazahKomponen.
 * Contoh: Komponen "Nilai Rapor" → sub: Semester 7, Semester 8, ...
 *
 * @property int    $id
 * @property int    $komponen_id
 * @property string $label        misal: "Semester 7"
 * @property int    $urutan
 */
class IjazahSubKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_ijazah_sub_komponen';
  protected $fillable = ['komponen_id', 'label', 'urutan'];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function komponen(): BelongsTo
  {
    return $this->belongsTo(IjazahKomponen::class, 'komponen_id');
  }

  public function nilaiSubKomponen(): HasMany
  {
    return $this->hasMany(IjazahNilaiSubKomponen::class, 'sub_komponen_id');
  }
}
