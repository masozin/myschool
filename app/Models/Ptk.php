<?php

namespace App\Models;

use App\Models\Scopes\SekolahScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ptk extends Model
{
  use HasFactory, SoftDeletes;

  protected $table = 'tabel_ptk';
  protected $fillable = ['nama_ptk', 'nip', 'jenis_kelamin', 'jabatan', 'jenis_kepegawaian', 'user_id', 'sekolah_id', 'photo', 'status', 'tanggal_keluar'];

  protected $casts = [
    'tanggal_keluar' => 'date',
  ];

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
