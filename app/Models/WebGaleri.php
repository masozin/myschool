<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebGaleri extends Model
{
  use HasFactory;

  protected $table = 'tabel_web_galeri';

  protected $fillable = [
    'sekolah_id',
    'kegiatan_id',
    'judul',
    'keterangan',
    'foto',
    'urutan',
  ];

  protected $casts = [
    'urutan' => 'integer',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function kegiatan(): BelongsTo
  {
    return $this->belongsTo(WebGaleriKegiatan::class, 'kegiatan_id');
  }
}
