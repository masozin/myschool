<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Header dokumen SKL (Surat Keterangan Lulus) per siswa.
 *
 * @property int         $id
 * @property int         $siswa_id
 * @property int         $sekolah_id
 * @property int         $semester_id
 * @property string|null $nomor_skl
 * @property \Carbon\Carbon|null $tanggal_terbit
 * @property string      $mode_input   jadi | manual
 * @property string      $status       draft | pengajuan | disetujui | ditolak
 * @property string|null $catatan
 * @property \Carbon\Carbon|null $generated_at
 */
class Skl extends Model
{
  use HasFactory, SoftDeletes;

  protected $table    = 'tabel_skl';
  protected $fillable = [
    'siswa_id',
    'sekolah_id',
    'semester_id',
    'nomor_skl',
    'tanggal_terbit',
    'mode_input',
    'status',
    'catatan',
    'generated_at',
  ];

  protected $casts = [
    'tanggal_terbit' => 'date',
    'generated_at'   => 'datetime',
    'siswa_id'       => 'integer',
    'sekolah_id'     => 'integer',
    'semester_id'    => 'integer',
  ];

  // ── Relasi ────────────────────────────────────────────────────────────

  public function siswa(): BelongsTo
  {
    return $this->belongsTo(Siswa::class);
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  public function semester(): BelongsTo
  {
    return $this->belongsTo(Semester::class);
  }

  public function nilais(): HasMany
  {
    return $this->hasMany(SklNilai::class, 'skl_id')
      ->orderBy(
        \App\Models\MapelMapping::select('urutan')
          ->whereColumn('tabel_mapel_mapping.id', 'tabel_skl_nilai.mapel_mapping_id')
          ->limit(1)
      );
  }

  public function nilaisLengkap()
  {
    return $this->nilais()
      ->with('mapelMapping.masterMapel.kelompok', 'mapelMapping.kelompok', 'komponens.komponen.subKomponen', 'komponens.nilaiSubKomponen')
      ->get();
  }

  // ── Helper ────────────────────────────────────────────────────────────

  public function nilaiPerKelompok(): array
  {
    $result = [];
    foreach ($this->nilais as $nilai) {
      $namaKelompok = $nilai->mapelMapping->kelompok->nama
        ?? $nilai->mapelMapping->masterMapel->kelompok->nama
        ?? 'Lainnya';
      $result[$namaKelompok][] = $nilai;
    }
    return $result;
  }

  public function nilaiRataRata(): float
  {
    $nilais = $this->nilais->pluck('nilai_akhir');
    if ($nilais->isEmpty()) return 0;
    return round($nilais->avg(), 2);
  }

  public function sudahLengkap(): bool
  {
    return $this->nilais->every(fn($n) => $n->nilai_akhir !== null);
  }

  public function aset(string $tipe): ?string
  {
    $aset = $this->sekolah->asets->where('tipe', $tipe)->first();
    return $aset ? $aset->absolute_path : null;
  }

  // ── Accessor ──────────────────────────────────────────────────────────

  public function getTahunPelajaranAttribute(): string
  {
    return $this->semester->tahun_pelajaran ?? '';
  }

  public function getNamaSekolahAttribute(): string
  {
    return $this->sekolah->detailTerbaru->nama_sekolah ?? '';
  }

  public function getKepalaSekolahAttribute(): string
  {
    return $this->sekolah->detailTerbaru->kepala_sekolah ?? '';
  }

  public function getNipKsAttribute(): string
  {
    return $this->sekolah->detailTerbaru->nip ?? '';
  }

  public function getNisAttribute(): string
  {
    return $this->siswa->registrasiTerbaru->nis ?? '';
  }

  // ── Status helper ─────────────────────────────────────────────────────

  public function isDraft(): bool
  {
    return $this->status === 'draft';
  }
  public function isPengajuan(): bool
  {
    return $this->status === 'pengajuan';
  }
  public function isDisetujui(): bool
  {
    return $this->status === 'disetujui';
  }
  public function isDitolak(): bool
  {
    return $this->status === 'ditolak';
  }
}
