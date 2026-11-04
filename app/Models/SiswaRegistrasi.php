<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\SekolahScope;

class SiswaRegistrasi extends Model
{
  use HasFactory;

  protected $table = 'tabel_siswa_registrasi';
  protected $fillable = [
    'siswa_id',
    'sekolah_id',
    'nis',
    'jenis_pendaftaran',
    'tingkat',
    'tanggal_diterima',
    'sekolah_asal',
    'tanggal_keluar',
    'alasan',
    'sekolah_tujuan',
    'status'
  ];
  public function siswa(): BelongsTo
  {
    return $this->belongsTo(Siswa::class, 'siswa_id');
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  protected static function booted()
  {
    static::addGlobalScope(new SekolahScope);
  }
}
