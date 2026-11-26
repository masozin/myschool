<?php

namespace App\Models;

use App\Models\Scopes\SekolahScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Ptk extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_ptk';
  protected $fillable = ['nama_ptk', 'gelar_depan', 'gelar_belakang', 'nip', 'nik', 'alamat', 'no_rekening', 'nama_bank', 'kode', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'jabatan', 'peran_tambahan', 'jenis_kepegawaian', 'user_id', 'sekolah_id', 'photo', 'status', 'tanggal_keluar'];

  /**
   * Daftar role baku aplikasi (nama role bersifat case sensitive).
   * Inilah satu-satunya sumber daftar role: RolesSeeder membuat
   * semuanya di database, dan validasi peran tambahan mengacu ke sini.
   */
  public const PERAN_BAKU = [
    'Admin',
    'Kepala Sekolah',
    'Operator Sekolah',
    'Guru',
    'Tata Usaha',
    'Pustakawan',
    'Bendahara',
  ];

  /**
   * Role yang dikelola lewat data PTK (jabatan utama + peran tambahan).
   * 'Admin' SENGAJA tidak termasuk: role itu dipasang manual di luar
   * form PTK, jadi sinkronisasi role dari PTK tidak boleh menghapusnya.
   */
  public const PERAN_TERKELOLA = [
    'Kepala Sekolah',
    'Operator Sekolah',
    'Guru',
    'Tata Usaha',
    'Pustakawan',
    'Bendahara',
  ];

  /**
   * Semua peran akses milik PTK ini: jabatan utama ditambah peran
   * tambahan, tanpa duplikat dan tanpa nilai kosong. Inilah daftar
   * yang diterapkan sebagai role Spatie pada akun user-nya.
   */
  public function semuaPeran(): array
  {
    $peran = array_merge(
      [(string) ($this->jabatan ?? '')],
      is_array($this->peran_tambahan) ? $this->peran_tambahan : []
    );

    $peran = array_map(fn($nama) => trim((string) $nama), $peran);

    return array_values(array_unique(array_filter($peran, fn($nama) => $nama !== '')));
  }

  /** Kode singkat guru: kolom kode bila diisi, selain itu inisial nama. */
  public function kodeGuru(): string
  {
    $kode = trim((string) ($this->kode ?? ''));
    if ($kode !== '') {
      return $kode;
    }
    $inisial = collect(explode(' ', trim((string) $this->nama_ptk)))
      ->filter()
      ->map(fn($kata) => Str::upper(Str::substr($kata, 0, 1)))
      ->implode('');

    return $inisial !== '' ? $inisial : '-';
  }

  protected $casts = [
    'tanggal_keluar' => 'date',
    'tanggal_lahir' => 'date',
    'peran_tambahan' => 'array',
  ];

  protected $appends = ['nama_lengkap'];

  /**
   * Nama resmi lengkap, dirangkai dari gelar depan + nama +
   * gelar belakang: "Drs. Budi Santoso, S.Pd., M.Pd".
   * Gelar ditulis persis seperti tersimpan (kapitalisasi gelar
   * seperti "S.Pd.SD" tidak diubah).
   */
  public function getNamaLengkapAttribute(): string
  {
    $nama = trim((string) $this->nama_ptk);
    $depan = trim((string) ($this->gelar_depan ?? ''));
    $belakang = trim((string) ($this->gelar_belakang ?? ''));

    $hasil = $depan !== '' ? $depan . ' ' . $nama : $nama;
    if ($belakang !== '') {
      $hasil .= ', ' . $belakang;
    }

    return $hasil;
  }

  /** Nama lengkap huruf besar semua, mis. untuk kop dokumen. */
  public function getNamaUpperAttribute(): string
  {
    return Str::upper($this->nama_lengkap);
  }

  /**
   * Nama lengkap dengan nama diri ditulis Proper Case
   * ("budi santoso" → "Budi Santoso"); gelar tetap persis
   * seperti tersimpan agar "S.Pd.SD" tidak berubah.
   */
  public function getNamaProperAttribute(): string
  {
    $nama = Str::title(Str::lower(trim((string) $this->nama_ptk)));
    $depan = trim((string) ($this->gelar_depan ?? ''));
    $belakang = trim((string) ($this->gelar_belakang ?? ''));

    $hasil = $depan !== '' ? $depan . ' ' . $nama : $nama;
    if ($belakang !== '') {
      $hasil .= ', ' . $belakang;
    }

    return $hasil;
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function sekolah(): BelongsTo
  {
    return $this->belongsTo(Sekolah::class);
  }

  // Relasi: Rombel di mana PTK ini menjadi Wali Kelas
  public function rombelDiampu(): HasMany
  {
    return $this->hasMany(Rombel::class, 'ptk_id');
  }

  // Relasi: Riwayat kepangkatan, terbaru dulu (untuk SPJ/daftar penerima)
  public function riwayatPangkat(): HasMany
  {
    return $this->hasMany(PtkRiwayatPangkat::class, 'ptk_id')
      ->orderByDesc('tmt')
      ->orderByDesc('id');
  }

  /** Entri riwayat pangkat terbaru (yang berlaku saat ini), atau null. */
  public function pangkatTerakhir(): ?PtkRiwayatPangkat
  {
    return $this->riwayatPangkat()->first();
  }

  /**
   * "Pangkat / Golongan" dari entri riwayat terbaru, mis.
   * "Penata / III/c" — dipakai daftar penerima SPJ. Null bila PTK
   * belum memiliki riwayat kepangkatan sama sekali.
   */
  public function getPangkatGolonganAttribute(): ?string
  {
    $terakhir = $this->pangkatTerakhir();

    if (!$terakhir) {
      return null;
    }

    return trim($terakhir->pangkat . ' / ' . $terakhir->golongan);
  }

  // Helper: Mengecek apakah PTK adalah wali kelas di semester tertentu
  public function isWaliKelasAktif($semester_id): bool
  {
    return $this->rombelDiampu()->where('semester_id', $semester_id)->exists();
  }

  protected static function booted()
  {
    static::addGlobalScope(new SekolahScope);
  }
}
