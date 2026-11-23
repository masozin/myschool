<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebGaleriKegiatan extends Model
{
  use HasFactory;

  protected $table = 'tabel_web_galeri_kegiatan';

  protected $fillable = [
    'sekolah_id',
    'nama',
    'tanggal',
    'keterangan',
  ];

  protected $casts = [
    'tanggal' => 'date',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function galeri(): HasMany
  {
    return $this->hasMany(WebGaleri::class, 'kegiatan_id')
      ->orderBy('urutan')
      ->latest('id');
  }

  /** Foto sampul album: foto pertama menurut urutan tampil. */
  public function fotoSampul(): ?WebGaleri
  {
    if ($this->relationLoaded('galeri')) {
      return $this->galeri->first();
    }

    return $this->galeri()->first();
  }
}
