<?php

namespace App\Models;

use App\Models\Scopes\SekolahScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningModule extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'learning_modules';

  protected $fillable = [
    'sekolah_id',
    'semester_id',
    'ptk_id',
    'mapel_master_id',
    'kurikulum_id',
    'nama_penyusun',
    'judul_modul',
    'jenjang',
    'fase',
    'kelas',
    'bab',
    'materi_pokok',
    'capaian_pembelajaran',
    'alur_tujuan_pembelajaran',
    'tujuan_pembelajaran',
    'kompetensi_awal',
    'pemahaman_bermakna',
    'pertanyaan_pemantik',
    'jumlah_jp',
    'jumlah_pertemuan',
    'model_pembelajaran',
    'metode_pembelajaran',
    'profil_pelajar_pancasila',
    'sarana_prasarana',
    'target_peserta_didik',
    'identifikasi_murid',
    'materi_pelajaran_list',
    'dimensi_profil_lulusan',
    'lintas_disiplin_ilmu',
    'praktik_pedagogis',
    'pemanfaatan_teknologi',
    'asesmen',
    'pengayaan',
    'remedial',
    'refleksi_peserta_didik',
    'refleksi_pendidik',
    'ai_provider',
    'ai_model',
    'ai_prompt',
    'ai_raw_response',
    'konten',
    'manual_inputs',
    'file_path',
    'status',
    'failed_reason',
    'generated_at',
    'created_by',
    'updated_by',
  ];

  protected $casts = [
    'tujuan_pembelajaran'      => 'array',
    'metode_pembelajaran'      => 'array',
    'profil_pelajar_pancasila' => 'array',
    'identifikasi_murid'       => 'array',
    'materi_pelajaran_list'    => 'array',
    'dimensi_profil_lulusan'   => 'array',
    'asesmen'                  => 'array',
    'pengayaan'                => 'array',
    'remedial'                 => 'array',
    'refleksi_peserta_didik'   => 'array',
    'refleksi_pendidik'        => 'array',
    'konten'                   => 'array',
    'manual_inputs'            => 'array',
    'generated_at'             => 'datetime',
  ];

  protected static function booted()
  {
    static::addGlobalScope(new SekolahScope);
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function ptk(): BelongsTo
  {
    return $this->belongsTo(Ptk::class);
  }

  public function mapel(): BelongsTo
  {
    return $this->belongsTo(MapelMaster::class, 'mapel_master_id');
  }

  public function kurikulum(): BelongsTo
  {
    return $this->belongsTo(Kurikulum::class);
  }

  public function meetings(): HasMany
  {
    return $this->hasMany(LearningModuleMeeting::class)
      ->orderBy('blok_urutan')
      ->orderBy('pertemuan_ke');
  }

  public function scopeStatus($query, string $status)
  {
    return $query->where('status', $status);
  }
}
