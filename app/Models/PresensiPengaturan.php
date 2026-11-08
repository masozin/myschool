<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PresensiPengaturan extends Model
{
  protected $table = 'tabel_presensi_pengaturan';

  protected $fillable = [
    'sekolah_id',
    'batas_koreksi_hari',
  ];

  /** Batas koreksi bawaan bila sekolah belum mengatur sendiri. */
  public const BATAS_DEFAULT = 7;

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  /**
   * Batas koreksi (hari) yang berlaku untuk satu sekolah:
   * nilai tersimpan, atau bawaan 7 hari bila belum diatur.
   */
  public static function batasUntuk(?int $sekolahId): int
  {
    if (!$sekolahId) {
      return self::BATAS_DEFAULT;
    }

    $nilai = static::where('sekolah_id', $sekolahId)->value('batas_koreksi_hari');

    return $nilai === null ? self::BATAS_DEFAULT : (int) $nilai;
  }
}
