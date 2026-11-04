<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Konfigurasi mode input ijazah per sekolah per semester.
 *
 * @property int    $id
 * @property int    $sekolah_id
 * @property int    $semester_id
 * @property string $mode_input    jadi | manual
 * @property bool   $is_terkunci
 */
class IjazahKonfigurasi extends Model
{
  use HasFactory;

  protected $table    = 'tabel_ijazah_konfigurasi';
  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'mode_input',
    'is_terkunci',
    'tempat_skl',
    'tanggal_skl',
  ];

  protected $casts = [
    'is_terkunci' => 'boolean',
    'tanggal_skl' => 'date',
  ];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

    // ── Helper ────────────────────────────────────────────────────────────

  /**
   * Ambil konfigurasi untuk sekolah + semester tertentu.
   * Gunakan semester_id dari session('semester_id').
   */
  public static function untukSekolah(int $sekolahId, int $semesterId): ?static
  {
    return static::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->first();
  }

  /**
   * Ambil atau buat konfigurasi default (mode: jadi, belum terkunci).
   * Dipanggil saat sekolah belum pernah mengatur konfigurasi semester ini.
   */
  public static function firstOrDefault(int $sekolahId, int $semesterId): static
  {
    return static::firstOrCreate(
      [
        'sekolah_id'  => $sekolahId,
        'semester_id' => $semesterId,
      ],
      [
        'mode_input'  => 'jadi',
        'is_terkunci' => false,
      ]
    );
  }

  /**
   * Kunci konfigurasi — dipanggil otomatis saat ijazah pertama dibuat.
   */
  public function kunci(): void
  {
    if (!$this->is_terkunci) {
      $this->update(['is_terkunci' => true]);
    }
  }

  // ── Shortcut ──────────────────────────────────────────────────────────

  public function isModeManual(): bool
  {
    return $this->mode_input === 'manual';
  }
  public function isModeJadi(): bool
  {
    return $this->mode_input === 'jadi';
  }

  /** Tahun pelajaran dari relasi semester — shortcut untuk view. */
  public function getTahunPelajaranAttribute(): string
  {
    return $this->semester->tahun_pelajaran ?? '';
  }
}
