<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Sekolah extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_sekolah';

  protected $fillable = ['npsn', 'jenjang'];

  protected $casts = [
    'npsn' => 'string',
  ];

  public function rombel(): HasMany
  {
    return $this->hasMany(Rombel::class, 'sekolah_id');
  }

  public function ptk(): HasMany
  {
    return $this->hasMany(Ptk::class, 'sekolah_id');
  }

  // Relasi ke siswa melalui tabel registrasi (HasManyThrough)
  public function siswa(): HasManyThrough
  {
    return $this->hasManyThrough(
      Siswa::class,
      SiswaRegistrasi::class,
      'sekolah_id', // Foreign key di tabel registrasi
      'id',         // Foreign key di tabel siswa
      'id',         // Local key di tabel sekolah
      'siswa_id'    // Local key di tabel registrasi
    );
  }

  public function sekolahDetail(): HasMany
  {
    return $this->hasMany(SekolahDetail::class, 'sekolah_id');
  }

  public function detailTerbaru(): HasOne
  {
    return $this->hasOne(SekolahDetail::class, 'sekolah_id')->latestOfMany();
  }

  public function detailPerSemester(): HasOne
  {
    // Mengambil data detail yang spesifik pada semester yang dipilih di Navbar
    return $this->hasOne(SekolahDetail::class, 'sekolah_id')
      ->where('semester_id', session('semester_id'));
  }

  public function siswaRegistrasi(): HasMany
  {
    return $this->hasMany(SiswaRegistrasi::class, 'sekolah_id');
  }

  public function fiturs()
  {
    return $this->belongsToMany(Fitur::class, 'tabel_fitur_sekolah')
      ->withPivot('aktif')
      ->withTimestamps();
  }

  public function hasFitur(string $kode): bool
  {
    return $this->fiturs()
      ->where('kode', $kode)
      ->wherePivot('aktif', true)
      ->exists();
  }

  public function asets(): HasMany
  {
    return $this->hasMany(SekolahLogo::class, 'sekolah_id');
  }

  // Helper: ambil aset berdasarkan tipe
  public function aset(string $tipe): ?SekolahLogo
  {
    return $this->asets->where('tipe', $tipe)->first();
  }
}
