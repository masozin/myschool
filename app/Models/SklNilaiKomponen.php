<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SklNilaiKomponen extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_nilai_komponen';
  protected $fillable = ['skl_nilai_id', 'komponen_id', 'nilai'];

  protected $casts = ['nilai' => 'decimal:2'];

  public function sklNilai(): BelongsTo
  {
    return $this->belongsTo(SklNilai::class, 'skl_nilai_id');
  }

  public function komponen(): BelongsTo
  {
    return $this->belongsTo(SklKomponen::class, 'komponen_id');
  }

  public function nilaiKomponenSub(): HasMany
  {
    return $this->hasMany(SklNilaiKomponenSub::class, 'skl_nilai_komponen_id')
      ->with('komponenSub')
      ->join('tabel_skl_komponen_sub', 'tabel_skl_nilai_komponen_sub.komponen_sub_id', '=', 'tabel_skl_komponen_sub.id')
      ->orderBy('tabel_skl_komponen_sub.urutan')
      ->select('tabel_skl_nilai_komponen_sub.*');
  }

  public function hitungDariSub(): void
  {
    $nilais = $this->nilaiKomponenSub->pluck('nilai')->filter(fn($v) => $v !== null);
    if ($nilais->isEmpty()) return;
    $this->update(['nilai' => round($nilais->avg(), 2)]);
  }

  public function kontribusi(): float
  {
    return round(($this->nilai ?? 0) * ($this->komponen->bobot / 100), 4);
  }
}
