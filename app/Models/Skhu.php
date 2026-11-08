<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skhu extends Model
{
  use HasFactory;

  protected $table    = 'tabel_skhu';
  protected $fillable = [
    'siswa_id',
    'sekolah_id',
    'semester_id',
    'nomor_surat',
    'nomor_ujian',
    'status',
    'generated_at',
    'snapshot_nama',
    'snapshot_nis',
    'snapshot_nisn',
    'snapshot_tempat_lahir',
    'snapshot_tgl_lahir',
    'snapshot_nama_ayah',
  ];

  protected $casts = [
    'generated_at'          => 'datetime',
    'snapshot_tgl_lahir'    => 'date',
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
   * Nilai-nilai SKHU, diurutkan berdasarkan urutan MapelMapping.
   * Join ke tabel_mapel_mapping untuk bisa order by urutan.
   */
  public function nilais(): HasMany
  {
    return $this->hasMany(SkhuNilai::class, 'skhu_id')
      ->join('tabel_mapel_mapping', 'tabel_skhu_nilai.mapel_mapping_id', '=', 'tabel_mapel_mapping.id')
      ->orderBy('tabel_mapel_mapping.urutan')
      ->select('tabel_skhu_nilai.*');
  }

  // ── Helper untuk Template ─────────────────────────────────────────────

  /**
   * Nilai dikelompokkan per kelompok mapel.
   *
   * @param bool $tampilKelompok
   *   true  → return ['Nama Kelompok' => [SkhuNilai, ...], ...]
   *   false → return ['__semua__'     => [SkhuNilai, ...]]  (flat, tanpa grouping)
   *
   * Contoh penggunaan di blade template:
   *   @foreach ($skhu->nilaiPerKelompok(true) as $kelompok => $nilais)
   *     @if ($kelompok !== '__semua__') <h4>{{ $kelompok }}</h4> @endif
   *     ...
   *   @endforeach
   */
  public function nilaiPerKelompok(bool $tampilKelompok = true): array
  {
    $result = [];

    // Urutan tampil selalu mengikuti urutan mapping (kelompok,
    // lalu urutan mapel) seperti pada formulir isian — BUKAN
    // urutan pembuatan baris nilai, yang dapat berbeda-beda
    // tergantung jalur simpan/impor yang lebih dulu berjalan
    $nilais = $this->nilais->sort(fn($a, $b) => [
      $a->mapelMapping?->kelompokTampil()?->urutan ?? 999,
      $a->mapelMapping?->urutan ?? 999,
    ] <=> [
      $b->mapelMapping?->kelompokTampil()?->urutan ?? 999,
      $b->mapelMapping?->urutan ?? 999,
    ]);

    foreach ($nilais as $nilai) {
      if ($tampilKelompok) {
        $namaKelompok = $nilai->mapelMapping?->kelompok?->nama
          ?? $nilai->mapelMapping?->masterMapel?->kelompok?->nama
          ?? 'Lainnya';
      } else {
        $namaKelompok = '__semua__';
      }

      $result[$namaKelompok][] = $nilai;
    }

    return $result;
  }

  /**
   * Nilai rata-rata semua mapel.
   */
  public function nilaiRataRata(): float
  {
    $nilais = $this->nilais->pluck('nilai');
    if ($nilais->isEmpty()) return 0;
    return round($nilais->avg(), 2);
  }

  /**
   * Ambil aset sekolah berdasarkan tipe.
   * Dipakai di template: $skhu->aset('ttd_ks')
   */
  public function aset(string $tipe): ?string
  {
    $aset = $this->sekolah->asets->where('tipe', $tipe)->first();
    return $aset ? $aset->absolute_path : null;
  }

  // ── Accessor ─────────────────────────────────────────────────────────

  public function getTahunPelajaranAttribute(): string
  {
    return $this->semester->tahun_pelajaran ?? '';
  }

  /**
   * '2024/2025' → '2024-2025'
   * Dipindahkan dari SkhuNilai ke sini karena dipakai untuk nama file PDF.
   */
  public function getTahunPelajaranCleanAttribute(): string
  {
    return str_replace('/', '-', $this->tahun_pelajaran);
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
  // Diisi saat SKHU difinalisasi, dikosongkan saat finalisasi
  // dibatalkan. Accessor *Cetak dipakai template cetak: membaca
  // snapshot bila ada, jatuh ke data induk bila belum ada.

  // ── Konfigurasi angkatan ─────────────────────────────────────────
  // Tanggal ujian, tanggal terbit, dan tempat terbit adalah data
  // satu angkatan (tabel_skhu_konfigurasi), bukan data per siswa.
  // Accessor di bawah mempertahankannya terbaca sebagai atribut
  // SKHU ($skhu->tanggal_terbit dst.) untuk cetakan & validasi.

  private ?SkhuKonfigurasi $konfigurasiCache = null;
  private bool $konfigurasiDimuat = false;

  public function konfigurasi(): ?SkhuKonfigurasi
  {
    if (!$this->konfigurasiDimuat) {
      $this->konfigurasiCache = ($this->sekolah_id && $this->semester_id)
        ? SkhuKonfigurasi::untukSekolah((int) $this->sekolah_id, (int) $this->semester_id)
        : null;
      $this->konfigurasiDimuat = true;
    }

    return $this->konfigurasiCache;
  }

  public function getTanggalMulaiUjianAttribute()
  {
    return $this->konfigurasi()?->tanggal_mulai_ujian;
  }

  public function getTanggalSelesaiUjianAttribute()
  {
    return $this->konfigurasi()?->tanggal_selesai_ujian;
  }

  public function getTanggalTerbitAttribute()
  {
    return $this->konfigurasi()?->tanggal_terbit;
  }

  public function getTempatTerbitAttribute()
  {
    return $this->konfigurasi()?->tempat_terbit;
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

  /**
   * Validasi kelengkapan data sebelum SKHU boleh difinalisasi.
   * Return array pesan error. Kosong = valid, siap difinalisasi.
   */

  private const TINGKAT_AKHIR = [
    'SD'  => 6,
    'MI'  => 6,
    'SMP' => 9,
    'MTs' => 9,
    'SMA' => 12,
    'MA'  => 12,
    'SMK' => 12,
  ];

  public function validasiFinalisasi(): array
  {
    $errors = [];

    // 1. Kolom header wajib
    $kolomWajib = [
      'nomor_surat'           => 'Nomor surat',
      'nomor_ujian'           => 'Nomor ujian',
      'tanggal_mulai_ujian'   => 'Tanggal mulai ujian',
      'tanggal_selesai_ujian' => 'Tanggal selesai ujian',
      'tanggal_terbit'        => 'Tanggal terbit',
    ];

    foreach ($kolomWajib as $kolom => $label) {
      if (empty($this->{$kolom})) {
        $errors[] = "{$label} belum diisi.";
      }
    }

    // 2. Relasi wajib ada
    if (!$this->siswa) {
      $errors[] = 'Data siswa tidak ditemukan / sudah dihapus.';
      return $errors;
    }
    if (!$this->sekolah) {
      $errors[] = 'Data sekolah tidak ditemukan.';
      return $errors;
    }

    // 3. Ambil tingkat siswa PADA SEMESTER SKHU ini (via RombelAnggota → Rombel)
    $rombelAnggota = RombelAnggota::where('siswa_id', $this->siswa_id)
      ->where('semester_id', $this->semester_id)
      ->with('rombel')
      ->first();

    if (!$rombelAnggota || !$rombelAnggota->rombel) {
      $errors[] = 'Siswa tidak terdaftar di rombel manapun pada semester ini, tidak bisa memvalidasi mapel.';
      return $errors;
    }

    $tingkat = $rombelAnggota->rombel->tingkat;
    $jenjang = $this->sekolah->jenjang;

    if (!$tingkat || !$jenjang) {
      $errors[] = 'Data jenjang/tingkat tidak lengkap, tidak bisa memvalidasi mapel.';
      return $errors;
    }

    // 4. SKHU hanya untuk siswa tingkat akhir jenjangnya
    $tingkatAkhir = self::TINGKAT_AKHIR[$jenjang] ?? null;

    if ($tingkatAkhir === null) {
      $errors[] = "Tingkat akhir untuk jenjang \"{$jenjang}\" belum terdaftar di sistem. Hubungi admin.";
      return $errors;
    }

    if ((int) $tingkat !== $tingkatAkhir) {
      $errors[] = "SKHU hanya berlaku untuk siswa tingkat akhir (tingkat {$tingkatAkhir}), siswa ini berada di tingkat {$tingkat}.";
      return $errors;
    }

    // 5. Mapel wajib: rumus berlaku yang sama seperti form SKHU
    //    (scope tunggal di MapelMapping), dengan kurikulum dari
    //    rombel siswa ini — mapel pokok yang dinonaktifkan
    //    sekolah tidak lagi mewajibkan nilai
    $kurikulumId = $rombelAnggota?->rombel?->kurikulum_id;

    $mapelWajibIds = MapelMapping::berlakuUntuk(
      (int) $this->sekolah_id,
      $jenjang,
      (int) $tingkat,
      (int) $this->semester_id,
      $kurikulumId !== null ? (int) $kurikulumId : null
    )->pluck('id');

    if ($mapelWajibIds->isEmpty()) {
      $errors[] = 'Belum ada mapping mapel untuk jenjang/tingkat/semester ini. Hubungi admin.';
      return $errors;
    }

    // 6. Mapel yang sudah punya baris nilai
    $nilaiList      = SkhuNilai::where('skhu_id', $this->id)->get();
    $mapelTerisiIds = $nilaiList->pluck('mapel_mapping_id');

    $mapelBelumIds = $mapelWajibIds->diff($mapelTerisiIds);

    if ($mapelBelumIds->isNotEmpty()) {
      $namaBelum = MapelMapping::whereIn('id', $mapelBelumIds)
        ->with('masterMapel')
        ->get()
        ->map(fn($m) => $m->masterMapel->nama_mapel ?? 'Mapel #' . $m->id);

      foreach ($namaBelum as $nama) {
        $errors[] = "Nilai mapel \"{$nama}\" belum diisi.";
      }
    }

    // 7. Nilai yang sudah ada barisnya tapi nilainya masih null
    $nilaiKosong = $nilaiList->whereNull('nilai')->count();
    if ($nilaiKosong > 0) {
      $errors[] = "Terdapat {$nilaiKosong} nilai mapel yang masih kosong.";
    }

    return $errors;
  }

  public function siapDifinalisasi(): bool
  {
    return empty($this->validasiFinalisasi());
  }
}
