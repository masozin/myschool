<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Konfigurasi mode input SKL per sekolah per semester.
 *
 * @property int    $id
 * @property int    $sekolah_id
 * @property int    $semester_id
 * @property string $mode_input    jadi | manual
 * @property bool   $is_terkunci   dikunci setelah SKL pertama dibuat (hanya mode_input)
 * @property string|null $tempat_terbit   Kota/tempat penerbitan SKL
 * @property \Carbon\Carbon|null $tanggal_terbit  Tanggal terbit default untuk SKL
 * @property string|null $prefix_nomor    Prefix nomor SKL, misal: "SKL"
 */
class SklKonfigurasi extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_konfigurasi';
  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'mode_input',
    'is_terkunci',
    'tempat_terbit',
    'tanggal_terbit',
    'prefix_nomor',
  ];

  protected $casts = [
    'is_terkunci'    => 'boolean',
    'tanggal_terbit' => 'date',
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

  public static function untukSekolah(int $sekolahId, int $semesterId): ?static
  {
    return static::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->first();
  }

  /**
   * Ambil atau buat konfigurasi default.
   * Default: mode jadi, belum terkunci, prefix SKL.
   */
  public static function firstOrDefault(int $sekolahId, int $semesterId): static
  {
    return static::firstOrCreate(
      [
        'sekolah_id'  => $sekolahId,
        'semester_id' => $semesterId,
      ],
      [
        'mode_input'    => 'jadi',
        'is_terkunci'   => false,
        'prefix_nomor'  => 'SKL',
        'tempat_terbit' => null,
        'tanggal_terbit' => null,
      ]
    );
  }

  /**
   * Kunci mode_input — dipanggil otomatis saat SKL pertama dibuat.
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

  /** Lokasi dan tanggal untuk tanda tangan SKL, misal: "Jakarta, 15 Juni 2025" */
  public function lokasiTanggalTerbit(): string
  {
    $tempat  = $this->tempat_terbit ?? '-';
    $tanggal = $this->tanggal_terbit
      ? $this->tanggal_terbit->translatedFormat('d F Y')
      : '-';
    return "{$tempat}, {$tanggal}";
  }

  public function getTahunPelajaranAttribute(): string
  {
    return $this->semester->tahun_pelajaran ?? '';
  }
}
