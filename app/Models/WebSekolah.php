<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebSekolah extends Model
{
  use HasFactory;

  protected $table = 'tabel_web_sekolah';

  protected $fillable = [
    'sekolah_id',
    'slug',
    'is_aktif',
    'tagline',
    'visi',
    'misi',
    'sambutan',
    'foto_hero',
    'akreditasi',
    'tahun_berdiri',
  ];

  protected $casts = [
    'is_aktif' => 'boolean',
    'tahun_berdiri' => 'integer',
  ];

  /** Slug yang tidak boleh dipakai karena bertabrakan dengan route internal /sekolah/*. */
  public const SLUG_TERLARANG = ['tabel', 'fitur', 'create', 'edit'];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  /** Web tampil ke publik hanya bila aktif DAN fitur web-sekolah sekolahnya aktif. */
  public function tampilPublik(): bool
  {
    return $this->is_aktif && $this->sekolah && $this->sekolah->hasFitur('web-sekolah');
  }
}
