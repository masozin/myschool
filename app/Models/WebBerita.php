<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class WebBerita extends Model
{
  use HasFactory;

  protected $table = 'tabel_web_berita';

  public const STATUS_DRAF = 'draf';
  public const STATUS_TERBIT = 'terbit';

  protected $fillable = [
    'sekolah_id',
    'user_id',
    'judul',
    'slug',
    'isi',
    'foto',
    'status',
    'diterbitkan_pada',
  ];

  protected $casts = [
    'diterbitkan_pada' => 'datetime',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function penulis(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function scopeTerbit($query)
  {
    return $query->where('status', self::STATUS_TERBIT)
      ->whereNotNull('diterbitkan_pada')
      ->where('diterbitkan_pada', '<=', now());
  }

  /** Ringkasan plain-text dari isi, untuk kartu berita di halaman publik. */
  public function getRingkasanAttribute(): string
  {
    return Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($this->isi))), 160);
  }

  /** Slug unik di dalam satu sekolah (suffix -2, -3, ... bila tabrakan). */
  public static function slugUnik(int $sekolahId, string $judul, ?int $kecualiId = null): string
  {
    $dasar = Str::slug($judul) ?: 'berita';
    $slug = $dasar;
    $n = 2;

    while (static::where('sekolah_id', $sekolahId)
      ->where('slug', $slug)
      ->when($kecualiId, fn($q) => $q->where('id', '!=', $kecualiId))
      ->exists()
    ) {
      $slug = $dasar . '-' . $n++;
    }

    return $slug;
  }
}
