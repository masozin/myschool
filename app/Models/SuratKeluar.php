<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratKeluar extends Model
{
  protected $table = 'tabel_surat_keluar';

  protected $fillable = [
    'sekolah_id',
    'kategori_id',
    'nomor_urut',
    'nomor_surat',
    'nomor_manual',
    'tanggal_surat',
    'tujuan',
    'perihal',
    'isi_ringkas',
    'lampiran',
    'penandatangan_ptk_id',
    'sifat',
    'status',
    'dibuat_oleh',
    'catatan',
  ];

  protected $casts = [
    'tanggal_surat' => 'date',
    'nomor_manual' => 'boolean',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function kategori(): BelongsTo
  {
    return $this->belongsTo(SuratKategori::class, 'kategori_id');
  }

  public function penandatangan(): BelongsTo
  {
    return $this->belongsTo(Ptk::class, 'penandatangan_ptk_id');
  }

  public function pembuat(): BelongsTo
  {
    return $this->belongsTo(User::class, 'dibuat_oleh');
  }

  public function berkas(): HasMany
  {
    return $this->hasMany(SuratBerkas::class, 'surat_id')->orderBy('jenis')->orderBy('id');
  }

  /** Label status untuk tampilan. */
  public function labelStatus(): string
  {
    return match ($this->status) {
      'terbit' => 'Terbit',
      'batal' => 'Dibatalkan',
      default => 'Draft',
    };
  }
}
