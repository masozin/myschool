<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KalenderPendidikan extends Model
{
  use HasFactory;

  protected $table = 'kalender_pendidikan';

  protected $fillable = [
    'tahun_pelajaran',
    'jenis_semester',
    'jenis',
    'tanggal_mulai',
    'tanggal_selesai',
    'is_libur',
    'kegiatan',
  ];

  protected $casts = [
    'tanggal_mulai'   => 'date',
    'tanggal_selesai' => 'date',
    'is_libur'        => 'boolean',
  ];
}
