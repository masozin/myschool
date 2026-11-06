<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MapelMapping extends Model
{
  use HasFactory;

  protected $table = 'tabel_mapel_mapping';

  protected $fillable = [
    'mapel_master_id',
    'mapel_kelompok_id', // kelompok penempatan mapping ini (bawaan dari master, bisa disesuaikan)
    'jenjang',
    'tingkat',
    'semester_id',
    'kurikulum_id', // NULL = berlaku untuk semua kurikulum
    'jenis', // pokok, tambahan (pokok berlaku untuk semua sekolah sesuai jenjang dan tingkat)
    'sekolah_id',  // untuk mapping lokal (jenis: tambahan)
    'urutan',
    'is_aktif',
  ];

  protected $casts = [
    'tingkat'           => 'integer',
    'urutan'            => 'integer',
    'semester_id'       => 'integer',
    'kurikulum_id'      => 'integer',
    'mapel_kelompok_id' => 'integer',
    'is_aktif'          => 'boolean',
  ];

  // -------------------------------------------------------
  // Relationships
  // -------------------------------------------------------

  public function masterMapel(): BelongsTo
  {
    return $this->belongsTo(MapelMaster::class, 'mapel_master_id');
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function skhuNilai(): HasMany
  {
    return $this->hasMany(SkhuNilai::class, 'mapel_mapping_id');
  }

  public function sklNilai(): HasMany
  {
    return $this->hasMany(SklNilai::class, 'mapel_mapping_id');
  }

  /** Kelompok penempatan milik mapping ini sendiri */
  public function kelompok(): BelongsTo
  {
    return $this->belongsTo(MapelKelompok::class, 'mapel_kelompok_id');
  }

  /** Kurikulum yang dilayani mapping ini (null = semua kurikulum) */
  public function kurikulum(): BelongsTo
  {
    return $this->belongsTo(Kurikulum::class);
  }

  /** Catatan sekolah-sekolah yang menonaktifkan mapping pokok ini */
  public function nonaktifSekolah(): HasMany
  {
    return $this->hasMany(MapelNonaktifSekolah::class, 'mapel_mapping_id');
  }

  /** Kelompok tampil: kelompok mapping, jatuh ke kelompok master bila kosong */
  public function kelompokTampil(): ?MapelKelompok
  {
    return $this->kelompok ?? $this->masterMapel?->kelompok;
  }

  // -------------------------------------------------------
  // SCOPES
  // -------------------------------------------------------

  /**
   * Satu-satunya rumus "mapel yang berlaku" untuk seorang siswa
   * di sebuah sekolah: master aktif, mapping aktif, kurikulum
   * cocok (atau mapping berlaku semua kurikulum), dan salah
   * satu dari:
   *   - pokok (sekolah_id null) yang TIDAK dinonaktifkan
   *     sekolah tersebut, atau
   *   - tambahan milik sekolah tersebut.
   *
   * $kurikulumId null berarti tanpa penyaringan kurikulum
   * (himpunan terbesar, untuk daftar tingkat sekolah).
   * Untuk konteks per siswa, isi dengan kurikulum rombel siswa.
   */
  public function scopeBerlakuUntuk(
    $query,
    int $sekolahId,
    string $jenjang,
    int $tingkat,
    int $semesterId,
    ?int $kurikulumId = null
  ) {
    return $query
      ->where('semester_id', $semesterId)
      ->where('jenjang', $jenjang)
      ->where('tingkat', $tingkat)
      ->where('is_aktif', true)
      ->whereHas('masterMapel', fn($q) => $q->where('is_aktif', true))
      ->when($kurikulumId !== null, fn($q) => $q->where(
        fn($q2) => $q2->whereNull('kurikulum_id')->orWhere('kurikulum_id', $kurikulumId)
      ))
      ->where(function ($q) use ($sekolahId) {
        $q->where(function ($q2) use ($sekolahId) {
          $q2->whereNull('sekolah_id')
            ->whereDoesntHave(
              'nonaktifSekolah',
              fn($q3) => $q3->where('sekolah_id', $sekolahId)
            );
        })->orWhere('sekolah_id', $sekolahId);
      });
  }
}
