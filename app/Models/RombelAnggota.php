<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RombelAnggota extends Model
{
  use HasFactory;

  protected $table = 'tabel_rombel_anggota';
  protected $fillable = ['siswa_id', 'rombel_id', 'semester_id'];

  public function siswa(): BelongsTo
  {
    return $this->belongsTo(Siswa::class);
  }

  public function rombel(): BelongsTo
  {
    return $this->belongsTo(Rombel::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }
}
