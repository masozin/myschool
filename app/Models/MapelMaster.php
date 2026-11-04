<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MapelMaster extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_mapel_master';

  protected $fillable = ['mapel_kelompok_id', 'kode', 'nama', 'nama_sub_mapel', 'nama_singkat', 'sekolah_id', 'is_aktif'];

  protected $casts = [
    'is_aktif' => 'boolean',
  ];

    // -------------------------------------------------------
    // Relationships
    // -------------------------------------------------------

  /** Kelompok mapel (Muatan Nasional, Peminatan, Kejuruan, dst) */
  public function kelompok(): BelongsTo
  {
    return $this->belongsTo(MapelKelompok::class, 'mapel_kelompok_id');
  }

  /** Kurikulum yang menggunakan mapel ini */
  public function kurikulum(): BelongsToMany
  {
    return $this->belongsToMany(Kurikulum::class, 'tabel_mapel_kurikulum');
  }

  /** Info jenjang & rentang kelas */
  public function mapelJenjang(): HasMany
  {
    return $this->hasMany(MapelJenjang::class, 'mapel_master_id');
  }

  /** Info jenjang & rentang kelas */
  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  /** Mapping ke kelas/semester di sekolah-sekolah */
  public function mapelMapping(): HasMany
  {
    return $this->hasMany(MapelMapping::class, 'mapel_master_id');
  }

    // -------------------------------------------------------
    // SCOPES
    // -------------------------------------------------------

  /**
   * Scope untuk mapel yang aktif dan sesuai jenjang
   */
  public function scopeAktifUntukJenjang($query, string $jenjang)
  {
    return $query->where('is_aktif', true)
      ->whereHas('mapelJenjang', function ($q) use ($jenjang) {
        $q->where('jenjang', $jenjang);
      })
      ->when(auth()->user()->hasRole('Operator Sekolah'), function ($q) {
        $sekolahId = auth()->user()->ptk->sekolah_id ?? null;
        if ($sekolahId) {
          $q->where(function ($sub) use ($sekolahId) {
            $sub->whereNull('sekolah_id')
              ->orWhere('sekolah_id', $sekolahId);
          });
        }
      });
  }
}
