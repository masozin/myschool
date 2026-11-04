<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Header dokumen ijazah per siswa.
 *
 * @property int         $id
 * @property int         $siswa_id
 * @property int         $sekolah_id
 * @property int         $semester_id
 * @property string|null $nomor_ijazah
 * @property \Carbon\Carbon|null $tanggal_terbit
 * @property string      $mode_input   jadi | manual
 * @property string      $status       draft | pengajuan | disetujui | ditolak
 * @property string|null $catatan
 * @property \Carbon\Carbon|null $generated_at
 */
class Ijazah extends Model
{
  use HasFactory, SoftDeletes;

  protected $table    = 'tabel_ijazah';
  protected $fillable = [
    'siswa_id',
    'sekolah_id',
    'semester_id',
    'nomor_ijazah',
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

  /**
   * Nilai per mapel.
   * Ordering dilakukan via subquery agar tidak konflik dengan eager load.
   */
  public function nilais(): HasMany
  {
    return $this->hasMany(IjazahNilai::class, 'ijazah_id')
      ->orderBy(
        \App\Models\MapelMapping::select('urutan')
          ->whereColumn('tabel_mapel_mapping.id', 'tabel_ijazah_nilai.mapel_mapping_id')
          ->limit(1)
      );
  }

  /**
   * Nilai dengan semua relasi untuk ditampilkan di view.
   * Panggil $ijazah->nilaisLengkap() di controller sebelum ke view.
   */
  public function nilaisLengkap()
  {
    return $this->nilais()
      ->with('mapelMapping.masterMapel.kelompok', 'komponens.komponen.subKomponen', 'komponens.nilaiSubKomponen')
      ->get();
  }

    // ── Helper ────────────────────────────────────────────────────────────

  /**
   * Nilai dikelompokkan per kelompok mapel (untuk template cetak).
   * Return: ['Muatan Nasional' => [IjazahNilai, ...], 'Muatan Lokal' => [...]]
   */
  public function nilaiPerKelompok(): array
  {
    $result = [];
    foreach ($this->nilais as $nilai) {
      $namaKelompok = $nilai->mapelMapping->masterMapel->kelompok->nama ?? 'Lainnya';
      $result[$namaKelompok][] = $nilai;
    }
    return $result;
  }

  /** Nilai rata-rata semua mapel. */
  public function nilaiRataRata(): float
  {
    $nilais = $this->nilais->pluck('nilai_akhir');
    if ($nilais->isEmpty()) return 0;
    return round($nilais->avg(), 2);
  }

  /** Cek apakah semua mapel sudah diisi nilainya. */
  public function sudahLengkap(): bool
  {
    return $this->nilais->every(fn($n) => $n->nilai_akhir !== null);
  }

  /** Ambil path aset sekolah berdasarkan tipe. */
  public function aset(string $tipe): ?string
  {
    $aset = $this->sekolah->asets->where('tipe', $tipe)->first();
    return $aset ? $aset->absolute_path : null;
  }

  // ── Accessor (shortcut untuk template cetak) ──────────────────────────

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
