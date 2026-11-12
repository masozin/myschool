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
  protected $fillable = ['nama_ptk', 'gelar_depan', 'gelar_belakang', 'nip', 'kode', 'jenis_kelamin', 'tanggal_lahir', 'jabatan', 'jenis_kepegawaian', 'user_id', 'sekolah_id', 'photo', 'status', 'tanggal_keluar'];

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
