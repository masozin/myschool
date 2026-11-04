<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SklNilai extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_nilai';
  protected $fillable = [
    'skl_id',
    'mapel_mapping_id',
    'nilai_akhir',
    'nilai_huruf',
  ];

  protected $casts = [
    'nilai_akhir' => 'decimal:2',
  ];

  public function skl(): BelongsTo
  {
    return $this->belongsTo(Skl::class);
  }

  public function mapelMapping(): BelongsTo
  {
    return $this->belongsTo(MapelMapping::class, 'mapel_mapping_id');
  }

  public function komponens(): HasMany
  {
    return $this->hasMany(SklNilaiKomponen::class, 'skl_nilai_id')
      ->with('komponen')
      ->join('tabel_skl_komponen', 'tabel_skl_nilai_komponen.komponen_id', '=', 'tabel_skl_komponen.id')
      ->orderBy('tabel_skl_komponen.urutan')
      ->select('tabel_skl_nilai_komponen.*');
  }

  public function hitungNilaiAkhir(): void
  {
    $total = $this->komponens
      ->sum(fn($k) => ($k->nilai ?? 0) * ($k->komponen->bobot / 100));

    $this->update([
      'nilai_akhir' => round($total, 2),
      'nilai_huruf' => static::angkaKeHuruf(round($total, 2)),
    ]);
  }

  public function getNamaMapelAttribute(): string
  {
    return $this->mapelMapping->masterMapel->nama ?? '-';
  }

  public function getNamaSubMapelAttribute(): ?string
  {
    return $this->mapelMapping->masterMapel->nama_sub_mapel ?? null;
  }

  public function getNamaKelompokAttribute(): string
  {
    return $this->mapelMapping->masterMapel->kelompok->nama ?? 'Lainnya';
  }

  public static function angkaKeHuruf(float $nilai): string
  {
    return match (true) {
      $nilai >= 90 => 'Sangat Baik',
      $nilai >= 75 => 'Baik',
      $nilai >= 60 => 'Cukup',
      default      => 'Kurang',
    };
  }
}
