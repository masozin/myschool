<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\SekolahScope;

class Rombel extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_rombel';
  protected $fillable = ['nama_rombel', 'tingkat', 'kurikulum_id', 'semester_id', 'ptk_id', 'sekolah_id'];

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function ptk(): BelongsTo
  {
    return $this->belongsTo(Ptk::class);
  }

  public function rombelAnggota(): HasMany
  {
    return $this->hasMany(RombelAnggota::class, 'rombel_id');
  }

  public function daftarSiswa()
  {
    return $this->belongsToMany(Siswa::class, 'tabel_rombel_anggota', 'rombel_id', 'siswa_id');
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function kurikulum(): BelongsTo
  {
    return $this->belongsTo(Kurikulum::class);
  }

  protected static function booted()
  {
    static::addGlobalScope(new SekolahScope);
  }
}
