<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Nilai per komponen untuk satu mapel dalam satu ijazah.
 *
 * Jika komponen TIDAK punya sub → 'nilai' diinput langsung.
 * Jika komponen PUNYA sub       → 'nilai' = rata-rata nilaiSubKomponen,
 *                                  dihitung via hitungDariSub().
 */
class IjazahNilaiKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_ijazah_nilai_komponen';
  protected $fillable = ['ijazah_nilai_id', 'komponen_id', 'nilai'];

  protected $casts = ['nilai' => 'decimal:2'];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function ijazahNilai(): BelongsTo
  {
    return $this->belongsTo(IjazahNilai::class, 'ijazah_nilai_id');
  }

  public function komponen(): BelongsTo
  {
    return $this->belongsTo(IjazahKomponen::class, 'komponen_id');
  }

  /**
   * Nilai per sub-komponen (hanya ada jika komponen punya sub).
   * Sudah urut sesuai urutan sub-komponen.
   */
  public function nilaiSubKomponen(): HasMany
  {
    return $this->hasMany(IjazahNilaiSubKomponen::class, 'ijazah_nilai_komponen_id')
      ->with('subKomponen')
      ->join('tabel_ijazah_sub_komponen', 'tabel_ijazah_nilai_sub_komponen.sub_komponen_id', '=', 'tabel_ijazah_sub_komponen.id')
      ->orderBy('tabel_ijazah_sub_komponen.urutan')
      ->select('tabel_ijazah_nilai_sub_komponen.*');
  }

    // ── Helper ────────────────────────────────────────────────────────────

  /**
   * Hitung nilai komponen dari rata-rata sub-komponennya, lalu simpan.
   * Dipanggil setelah semua IjazahNilaiSubKomponen disimpan.
   */
  public function hitungDariSub(): void
  {
    $nilais = $this->nilaiSubKomponen->pluck('nilai')->filter(fn($v) => $v !== null);

    if ($nilais->isEmpty()) return;

    $this->update(['nilai' => round($nilais->avg(), 2)]);
  }

  /** Kontribusi komponen ini ke nilai akhir mapel (nilai × bobot / 100). */
  public function kontribusi(): float
  {
    return round(($this->nilai ?? 0) * ($this->komponen->bobot / 100), 4);
  }
}
