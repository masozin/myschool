<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Siswa extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_siswa';
  protected $fillable = [
    'nama_siswa',
    'nisn',
    'jenis_kelamin',
    'tempat_lahir',
    'tgl_lahir',
    'nik',
    'agama',
    'alamat',
    'disabilitas',
    'nama_ayah',
    'tahun_ayah',
    'nik_ayah',
    'pekerjaan_ayah',
    'nama_ibu',
    'tahun_ibu',
    'nik_ibu',
    'pekerjaan_ibu',
    'no_hp',
    'nama_wali',
    'tahun_wali',
    'nik_wali',
    'pekerjaan_wali',
    'no_hp_wali',
    'status',
    'user_id',
    'photo'
  ];

  protected $casts = [
    'tgl_lahir' => 'datetime:Y-m-d',
  ];

  public function rombelAnggota(): HasMany
  {
    return $this->hasMany(RombelAnggota::class, 'siswa_id');
  }

  // Siswa bisa memiliki banyak riwayat rombel (pindah kelas tiap semester)
  public function rombels()
  {
    return $this->belongsToMany(Rombel::class, 'tabel_rombel_anggota', 'siswa_id', 'rombel_id');
  }

  public function siswaRegistrasi(): HasMany
  {
    return $this->hasMany(SiswaRegistrasi::class, 'siswa_id');
  }

  public function registrasiTerbaru()
  {
    // Mengambil registrasi terbaru
    return $this->hasOne(SiswaRegistrasi::class, 'siswa_id')->latestOfMany();
  }

  // Relasi ke SKHU
  public function skhu(): HasMany
  {
    return $this->hasMany(\App\Models\Skhu::class, 'siswa_id');
  }

  // Relasi ke SKL
  public function skl(): HasMany
  {
    return $this->hasMany(\App\Models\Skl::class, 'siswa_id');
  }
}
