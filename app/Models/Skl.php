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
    'status',
    'catatan',
    'generated_at',
    'snapshot_nama',
    'snapshot_nis',
    'snapshot_nisn',
    'snapshot_tempat_lahir',
    'snapshot_tgl_lahir',
    'snapshot_nama_ayah',
  ];

  protected $casts = [
    'generated_at'   => 'datetime',
    'snapshot_tgl_lahir' => 'date',
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
      ->with('mapelMapping.masterMapel.kelompok', 'mapelMapping.kelompok', 'komponens.komponen.komponenSub', 'komponens.nilaiKomponenSub')
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
    return $this->snapshot_nis ?? $this->siswa->registrasiTerbaru->nis ?? '';
  }

  // ── Snapshot identitas ──────────────────────────────────────────────
  // Diisi saat SKL disetujui, dikosongkan saat persetujuan dibatalkan
  // atau SKL ditolak. Accessor *Cetak dipakai template cetak: membaca
  // snapshot bila ada, jatuh ke data induk bila belum ada.

  // ── Konfigurasi angkatan ─────────────────────────────────────────
  // Mode input dan tanggal terbit adalah milik konfigurasi SKL
  // (tabel_skl_konfigurasi), bukan baris SKL. Accessor ini
  // mempertahankannya terbaca sebagai atribut SKL
  // ($skl->mode_input, $skl->tanggal_terbit) untuk form & cetakan.

  private ?SklKonfigurasi $konfigurasiCache = null;
  private bool $konfigurasiDimuat = false;

  public function konfigurasi(): ?SklKonfigurasi
  {
    if (!$this->konfigurasiDimuat) {
      $this->konfigurasiCache = ($this->sekolah_id && $this->semester_id)
        ? SklKonfigurasi::untukSekolah((int) $this->sekolah_id, (int) $this->semester_id)
        : null;
      $this->konfigurasiDimuat = true;
    }

    return $this->konfigurasiCache;
  }

  public function getModeInputAttribute(): string
  {
    return $this->konfigurasi()?->mode_input ?? 'jadi';
  }

  public function getTanggalTerbitAttribute()
  {
    return $this->konfigurasi()?->tanggal_terbit;
  }

  public function isiSnapshotIdentitas(): void
  {
    $siswa = $this->siswa;

    $this->snapshot_nama         = $siswa?->nama_siswa;
    $this->snapshot_nis          = $this->siswa?->registrasiTerbaru?->nis;
    $this->snapshot_nisn         = $siswa?->nisn;
    $this->snapshot_tempat_lahir = $siswa?->tempat_lahir;
    $this->snapshot_tgl_lahir    = $siswa?->tgl_lahir;
    $this->snapshot_nama_ayah    = $siswa?->nama_ayah;
  }

  public function hapusSnapshotIdentitas(): void
  {
    $this->snapshot_nama         = null;
    $this->snapshot_nis          = null;
    $this->snapshot_nisn         = null;
    $this->snapshot_tempat_lahir = null;
    $this->snapshot_tgl_lahir    = null;
    $this->snapshot_nama_ayah    = null;
  }

  public function getNamaCetakAttribute(): ?string
  {
    return $this->snapshot_nama ?? $this->siswa?->nama_siswa;
  }

  public function getNisnCetakAttribute(): ?string
  {
    return $this->snapshot_nisn ?? $this->siswa?->nisn;
  }

  public function getTempatLahirCetakAttribute(): ?string
  {
    return $this->snapshot_tempat_lahir ?? $this->siswa?->tempat_lahir;
  }

  public function getTglLahirCetakAttribute()
  {
    return $this->snapshot_tgl_lahir ?? $this->siswa?->tgl_lahir;
  }

  public function getNamaAyahCetakAttribute(): ?string
  {
    return $this->snapshot_nama_ayah ?? $this->siswa?->nama_ayah;
  }

  /**
   * Nama kurikulum siswa ini, dari rombelnya pada semester dokumen
   * ini — dipakai kop tabel nilai di template cetak. null bila
   * rombel/kurikulum tidak ditemukan (template jatuh ke teks lama).
   */
  public function getNamaKurikulumAttribute(): ?string
  {
    $rombelAnggota = RombelAnggota::where('siswa_id', $this->siswa_id)
      ->where('semester_id', $this->semester_id)
      ->with('rombel.kurikulum')
      ->first();

    return $rombelAnggota?->rombel?->kurikulum?->nama;
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
