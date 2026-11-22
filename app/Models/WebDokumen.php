<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WebDokumen extends Model
{
  use HasFactory;

  protected $table = 'tabel_web_dokumen';

  protected $fillable = [
    'sekolah_id',
    'judul',
    'kategori',
    'tahun',
    'keterangan',
    'berkas',
    'nama_berkas',
    'ukuran',
    'is_tampil',
  ];

  protected $casts = [
    'tahun' => 'integer',
    'ukuran' => 'integer',
    'is_tampil' => 'boolean',
  ];

  /** Kategori tetap agar halaman publik rapi berkelompok. Urutan di sini = urutan tampil. */
  public const KATEGORI = [
    'akreditasi' => 'Akreditasi',
    'kurikulum' => 'Kurikulum & KOSP',
    'perencanaan' => 'Perencanaan (RKT / RKJM / RKAS)',
    'kalender' => 'Kalender & Jadwal',
    'lainnya' => 'Lainnya',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  public function labelKategori(): string
  {
    return self::KATEGORI[$this->kategori] ?? 'Lainnya';
  }

  /** Ekstensi berkas huruf kecil, mis. pdf / jpg / png. */
  public function ekstensi(): string
  {
    return strtolower(pathinfo($this->berkas, PATHINFO_EXTENSION));
  }

  public function isPdf(): bool
  {
    return $this->ekstensi() === 'pdf';
  }

  /** Ukuran berkas ramah baca: "842 KB" / "2,4 MB". */
  public function ukuranTampil(): ?string
  {
    if (!$this->ukuran) {
      return null;
    }

    if ($this->ukuran >= 1048576) {
      return number_format($this->ukuran / 1048576, 1, ',', '.') . ' MB';
    }

    return max(1, (int) round($this->ukuran / 1024)) . ' KB';
  }
}
