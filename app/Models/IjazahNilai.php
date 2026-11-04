<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Nilai akhir per mapel dalam satu ijazah.
 *
 * Mode 'jadi'  : nilai_akhir diisi langsung oleh operator.
 * Mode 'manual': nilai_akhir dikalkulasi dari komponen berbobot,
 *                panggil hitungNilaiAkhir() setelah semua komponen disimpan.
 *
 * @property int        $id
 * @property int        $ijazah_id
 * @property int        $mapel_mapping_id
 * @property float|null $nilai_akhir
 * @property string|null $nilai_huruf
 */
class IjazahNilai extends Model
{
  use HasFactory;

  protected $table    = 'tabel_ijazah_nilai';
  protected $fillable = [
    'ijazah_id',
    'mapel_mapping_id',
    'nilai_akhir',
    'nilai_huruf',
  ];

  protected $casts = [
    'nilai_akhir' => 'decimal:2',
  ];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function ijazah(): BelongsTo
  {
    return $this->belongsTo(Ijazah::class);
  }

  /**
   * MapelMapping → bisa lanjut ke masterMapel → kelompok
   * Contoh: $ijazahNilai->mapelMapping->masterMapel->nama
   *         $ijazahNilai->mapelMapping->masterMapel->kelompok->nama
   */
  public function mapelMapping(): BelongsTo
  {
    return $this->belongsTo(MapelMapping::class, 'mapel_mapping_id');
  }

  /**
   * Komponen nilai (hanya ada jika mode = 'manual').
   * Sudah include eager load komponen & urut sesuai konfigurasi.
   */
  public function komponens(): HasMany
  {
    return $this->hasMany(IjazahNilaiKomponen::class, 'ijazah_nilai_id')
      ->with('komponen')
      ->join('tabel_ijazah_komponen', 'tabel_ijazah_nilai_komponen.komponen_id', '=', 'tabel_ijazah_komponen.id')
      ->orderBy('tabel_ijazah_komponen.urutan')
      ->select('tabel_ijazah_nilai_komponen.*');
  }

    // ── Helper ────────────────────────────────────────────────────────────

  /**
   * Hitung nilai_akhir dari komponen berbobot, lalu simpan ke DB.
   *   nilai_akhir = Σ (nilai_komponen × bobot / 100)
   * Dipanggil setelah semua IjazahNilaiKomponen disimpan.
   */
  public function hitungNilaiAkhir(): void
  {
    $total = $this->komponens
      ->sum(fn($k) => ($k->nilai ?? 0) * ($k->komponen->bobot / 100));

    $this->update([
      'nilai_akhir' => round($total, 2),
      'nilai_huruf' => static::angkaKeHuruf(round($total, 2)),
    ]);
  }

  /**
   * Shortcut nama mapel dari chain relasi.
   * Menghindari penulisan panjang di blade.
   */
  public function getNamaMapelAttribute(): string
  {
    return $this->mapelMapping->masterMapel->nama ?? '-';
  }

  public function getNamaSubMapelAttribute(): ?string
  {
    return $this->mapelMapping->masterMapel->nama_sub_mapel ?? null;
  }

  public function getNamaKelompokAttribute(): string
  {
    return $this->mapelMapping->masterMapel->kelompok->nama ?? 'Lainnya';
  }

  /**
   * Konversi angka ke predikat huruf.
   * Sesuaikan range dengan aturan kelulusan sekolah.
   */
  public static function angkaKeHuruf(float $nilai): string
  {
    return match (true) {
      $nilai >= 90 => 'Sangat Baik',
      $nilai >= 75 => 'Baik',
      $nilai >= 60 => 'Cukup',
      default      => 'Kurang',
    };
  }
}
