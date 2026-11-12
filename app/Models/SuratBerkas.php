<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratBerkas extends Model
{
  protected $table = 'tabel_surat_berkas';

  protected $fillable = [
    'surat_id',
    'jenis',
    'nama_asli',
    'path',
    'mime',
    'ukuran',
    'diunggah_oleh',
  ];

  public function surat(): BelongsTo
  {
    return $this->belongsTo(SuratKeluar::class, 'surat_id');
  }

  public function pengunggah(): BelongsTo
  {
    return $this->belongsTo(User::class, 'diunggah_oleh');
  }

  /** Ukuran terbaca manusia, mis. "1,2 MB". */
  public function ukuranLabel(): string
  {
    $bytes = (int) $this->ukuran;
    if ($bytes >= 1048576) {
      return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
      return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
    return $bytes . ' B';
  }
}
