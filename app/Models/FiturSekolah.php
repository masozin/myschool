<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiturSekolah extends Model
{
  protected $table    = 'tabel_fitur_sekolah';
  protected $fillable = ['sekolah_id', 'fitur_id', 'aktif'];

  public function sekolah()
  {
    return $this->belongsTo(Sekolah::class);
  }
  public function fitur()
  {
    return $this->belongsTo(Fitur::class);
  }
}
