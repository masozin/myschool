<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratPengaturan extends Model
{
  protected $table = 'tabel_surat_pengaturan';

  public const FORMAT_BAWAAN = '{no:3}/{kode}/{bulan_romawi}/{tahun}';

  protected $fillable = [
    'sekolah_id',
    'format_nomor',
    'nomor_terakhir',
    'tahun_berjalan',
  ];

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class, 'sekolah_id');
  }

  /** Pengaturan sekolah ini; dibuat dengan nilai bawaan bila belum ada. */
  public static function untukSekolah(int $sekolahId): self
  {
    return static::firstOrCreate(
      ['sekolah_id' => $sekolahId],
      ['format_nomor' => self::FORMAT_BAWAAN, 'nomor_terakhir' => 0]
    );
  }
}
