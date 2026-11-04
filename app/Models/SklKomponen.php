<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Konfigurasi komponen nilai SKL per jenjang per semester.
 *
 * Komponen bisa punya sub-komponen (misal: Rapor → Sem 7, Sem 8, ...).
 * Jika ada sub → nilai komponen = rata-rata sub (diisi otomatis).
 * Jika tidak ada sub → nilai komponen diinput langsung.
 */
class SklKomponen extends Model
{
  use HasFactory, SoftDeletes;

  protected $table    = 'tabel_skl_komponen';
  protected $fillable = [
    'semester_id',
    'jenjang',
    'kode',
    'label',
    'bobot',
    'keterangan',
    'urutan',
  ];

  protected $casts = [
    'semester_id' => 'integer',
    'bobot'       => 'integer',
    'urutan'      => 'integer',
  ];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function subKomponen(): HasMany
  {
    return $this->hasMany(SklSubKomponen::class, 'komponen_id')->orderBy('urutan');
  }

  public function nilaiKomponen(): HasMany
  {
    return $this->hasMany(SklNilaiKomponen::class, 'komponen_id');
  }

  // ── Scope ─────────────────────────────────────────────────────────────

  /**
   * Filter komponen untuk semester + jenjang tertentu.
   */
  public function scopeUntuk($query, int $semesterId, string $jenjang)
  {
    return $query
      ->where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->orderBy('urutan');
  }

  // ── Helper ────────────────────────────────────────────────────────────

  public function punyaSub(): bool
  {
    return $this->subKomponen()->exists();
  }

  /** Total bobot semua komponen untuk semester + jenjang tertentu. */
  public static function totalBobot(int $semesterId, string $jenjang): int
  {
    return (int) static::where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->sum('bobot');
  }
}
