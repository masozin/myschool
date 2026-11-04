<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LearningModuleMeeting extends Model
{
  use HasFactory;

  protected $table = 'learning_module_meetings';

  protected $fillable = [
    'learning_module_id',
    'pertemuan_ke',
    'blok_judul',
    'blok_urutan',
    'rentang_pertemuan',
    'jp',
    'kegiatan_awal_list',
    'kegiatan_inti_list',
    'kegiatan_penutup_list',
    'lampiran',
  ];

  protected $casts = [
    'kegiatan_awal_list'    => 'array',
    'kegiatan_inti_list'    => 'array',
    'kegiatan_penutup_list' => 'array',
    'lampiran'              => 'array',
  ];

  public function learningModule(): BelongsTo
  {
    return $this->belongsTo(LearningModule::class);
  }
}
