<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konfigurasi SKHU per sekolah + semester (satu baris per angkatan).
 *
 * Memegang data yang berlaku untuk seluruh angkatan: tanggal mulai
 * dan selesai ujian, tanggal terbit, dan tempat terbit. Sebelumnya
 * keempatnya tersimpan berulang pada setiap baris tabel_skhu; kini
 * tabel_skhu hanya menyimpan data per siswa (nomor & status), dan
 * model Skhu membacanya dari sini lewat accessor.
 */
class SkhuKonfigurasi extends Model
{
  protected $table = 'tabel_skhu_konfigurasi';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'tanggal_mulai_ujian',
    'tanggal_selesai_ujian',
    'tanggal_terbit',
    'tempat_terbit',
  ];

  protected $casts = [
    'tanggal_mulai_ujian'   => 'date',
    'tanggal_selesai_ujian' => 'date',
    'tanggal_terbit'        => 'date',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public static function untukSekolah(int $sekolahId, int $semesterId): ?static
  {
    return static::where('sekolah_id', $sekolahId)
      ->where('semester_id', $semesterId)
      ->first();
  }
}
