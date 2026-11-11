<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalPengaturan extends Model
{
  protected $table = 'tabel_jadwal_pengaturan';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'nama',
    'tingkat_dari',
    'tingkat_sampai',
    'kerangka',
  ];

  protected $casts = [
    'kerangka' => 'array',
    'tingkat_dari' => 'integer',
    'tingkat_sampai' => 'integer',
  ];

  /** Label rentang tingkat model ini, mis. "Tingkat 1–3". */
  public function labelRentang(): string
  {
    if ($this->tingkat_dari === null && $this->tingkat_sampai === null) {
      return 'Semua tingkat';
    }
    if ($this->tingkat_dari !== null && $this->tingkat_sampai !== null) {
      return $this->tingkat_dari === $this->tingkat_sampai
        ? 'Tingkat ' . $this->tingkat_dari
        : 'Tingkat ' . $this->tingkat_dari . '–' . $this->tingkat_sampai;
    }

    return $this->tingkat_dari !== null
      ? 'Tingkat ' . $this->tingkat_dari . ' ke atas'
      : 'Tingkat ' . $this->tingkat_sampai . ' ke bawah';
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }
}
