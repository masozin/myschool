<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Presensi extends Model
{
  use HasFactory;

  protected $table = 'tabel_presensi';

  protected $fillable = [
    'siswa_id',
    'rombel_id',
    'semester_id',
    'tanggal',
    'status',
    'keterangan',
    'input_by',
  ];

  protected $casts = [
    'tanggal' => 'date:Y-m-d',
  ];

  // Konstanta status, biar tidak salah ketik string di controller/view
  public const STATUS_HADIR = 'hadir';
  public const STATUS_IZIN  = 'izin';
  public const STATUS_SAKIT = 'sakit';
  public const STATUS_ALPA  = 'alpa';

  public function siswa(): BelongsTo
  {
    return $this->belongsTo(Siswa::class, 'siswa_id');
  }

  public function rombel(): BelongsTo
  {
    return $this->belongsTo(Rombel::class, 'rombel_id');
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }

  public function inputOleh(): BelongsTo
  {
    return $this->belongsTo(\App\Models\User::class, 'input_by');
  }

  // Scope bantu untuk query rekap
  public function scopeTanggal($query, $tanggal)
  {
    return $query->whereDate('tanggal', $tanggal);
  }

  public function scopeRombel($query, $rombelId)
  {
    return $query->where('rombel_id', $rombelId);
  }
}
