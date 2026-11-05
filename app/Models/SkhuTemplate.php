<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SkhuTemplate extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skhu_template';
  protected $fillable = ['jenjang', 'semester_id', 'nama_file', 'keterangan', 'is_active'];

  protected $casts = ['is_active' => 'boolean'];

  public function getViewNameAttribute(): string
  {
    // Sesuaikan prefix dengan lokasi folder di resources/views/
    return 'templates.skhu-template.' . str_replace('/', '.', $this->nama_file);
  }

  public function getBladePathAttribute(): string
  {
    // Sesuaikan path absolut
    return resource_path('views/templates/skhu-template/' . $this->nama_file . '.blade.php');
  }

  // Cek apakah file blade ada
  public function templateAda(): bool
  {
    return file_exists($this->blade_path);
  }

  // Ambil template aktif untuk jenjang + tahun pelajaran tertentu
  public static function aktifUntuk(string $jenjang, string $semesterId): ?self
  {
    return static::where('jenjang', $jenjang)
      ->where('semester_id', $semesterId)
      ->where('is_active', true)
      ->first();
  }
}
