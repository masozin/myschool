<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratKategori extends Model
{
  protected $table = 'tabel_surat_kategori';

  protected $fillable = [
    'sekolah_id',
    'kode',
    'nama',
    'urutan',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function surat(): HasMany
  {
    return $this->hasMany(SuratKeluar::class, 'kategori_id');
  }
}
