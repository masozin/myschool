<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fitur extends Model
{
  protected $table    = 'tabel_fitur';
  protected $fillable = ['kode', 'nama', 'deskripsi'];

  public function sekolahs()
  {
    return $this->belongsToMany(Sekolah::class, 'tabel_fitur_sekolah')
      ->withPivot('aktif')
      ->withTimestamps();
  }
}
