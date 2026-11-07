<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Template cetak SKL — pasangan tabel_skhu_template untuk SKHU.
 *
 * Satu baris mendaftarkan satu file Blade di
 * resources/views/templates/skl-template/ untuk satu jenjang +
 * semester. Bila belum ada baris yang aktif, cetakan SKL memakai
 * file bawaan 'skl-2425-sd' seperti sebelum tabel ini ada.
 */
class SklTemplate extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skl_template';
  protected $fillable = ['jenjang', 'semester_id', 'kurikulum_id', 'nama_file', 'keterangan', 'is_active'];

  protected $casts = ['is_active' => 'boolean'];

  public function semester()
  {
    return $this->belongsTo(Semester::class, 'semester_id');
  }

  public function kurikulum()
  {
    return $this->belongsTo(Kurikulum::class, 'kurikulum_id');
  }

  public function getViewNameAttribute(): string
  {
    return 'templates.skl-template.' . str_replace('/', '.', $this->nama_file);
  }

  public function getBladePathAttribute(): string
  {
    return resource_path('views/templates/skl-template/' . $this->nama_file . '.blade.php');
  }

  public function templateAda(): bool
  {
    return file_exists($this->blade_path);
  }

  public static function aktifUntuk(string $jenjang, string $semesterId): ?self
  {
    return static::where('jenjang', $jenjang)
      ->where('semester_id', $semesterId)
      ->where('is_active', true)
      ->first();
  }
}
