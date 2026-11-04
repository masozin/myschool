<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SekolahLogo extends Model
{
  use HasFactory;

  protected $table    = 'tabel_sekolah_logo';
  protected $fillable = ['sekolah_id', 'tipe', 'path_file', 'keterangan'];

  // Tipe yang tersedia
  const TIPE_TTD_KS         = 'ttd_ks';
  const TIPE_STEMPEL         = 'stempel';
  const TIPE_LOGO_SEKOLAH    = 'logo_sekolah';
  const TIPE_LOGO_KABUPATEN  = 'logo_kabupaten';
  const TIPE_KOP_POTRAIT     = 'kop_potrait';
  const TIPE_KOP_LANDSCAPE   = 'kop_landscape';
  const TIPE_KOP_KARTU       = 'kop_kartu';

  public static array $tipeLabel = [
    self::TIPE_TTD_KS         => 'Tanda Tangan Kepala Sekolah',
    self::TIPE_STEMPEL         => 'Stempel Sekolah',
    self::TIPE_LOGO_SEKOLAH    => 'Logo Sekolah',
    self::TIPE_LOGO_KABUPATEN  => 'Logo Kabupaten/Kota',
    self::TIPE_KOP_POTRAIT     => 'Kop Sekolah (Potrait)',
    self::TIPE_KOP_LANDSCAPE   => 'Kop Sekolah (Landscape)',
    self::TIPE_KOP_KARTU       => 'Kop Sekolah (Kartu)',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  // Helper: ambil URL publik file
  public function getUrlAttribute(): string
  {
    return asset('storage/' . $this->path_file);
  }

  // Helper: ambil path absolut (untuk DomPDF yang butuh path file, bukan URL)
  public function getAbsolutePathAttribute(): string
  {
    return storage_path('app/public/' . $this->path_file);
  }
}
