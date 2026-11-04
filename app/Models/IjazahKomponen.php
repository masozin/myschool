<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Konfigurasi komponen nilai ijazah per jenjang per tahun pelajaran.
 *
 * Komponen bisa punya sub-komponen (misal: Rapor → Sem 7, Sem 8, ...).
 * Jika ada sub → nilai komponen = rata-rata sub (diisi otomatis).
 * Jika tidak ada sub → nilai komponen diinput langsung.
 */
class IjazahKomponen extends Model
{
    use HasFactory, SoftDeletes;

    protected $table    = 'tabel_ijazah_komponen';
    protected $fillable = [
        'jenjang',
        'tahun_pelajaran',
        'kode',
        'label',
        'bobot',
        'keterangan',
        'urutan',
    ];

    // ── Relasi ────────────────────────────────────────────────────────────

    public function subKomponen(): HasMany
    {
        return $this->hasMany(IjazahSubKomponen::class, 'komponen_id')
            ->orderBy('urutan');
    }

    public function nilaiKomponen(): HasMany
    {
        return $this->hasMany(IjazahNilaiKomponen::class, 'komponen_id');
    }

    // ── Scope ─────────────────────────────────────────────────────────────

    public function scopeUntuk($query, string $jenjang, string $tahunPelajaran)
    {
        return $query
            ->where('jenjang', $jenjang)
            ->where('tahun_pelajaran', $tahunPelajaran)
            ->orderBy('urutan');
    }

    // ── Helper ────────────────────────────────────────────────────────────

    /** Cek apakah komponen ini punya sub-komponen. */
    public function punyaSub(): bool
    {
        return $this->subKomponen()->exists();
    }

    /** Total bobot semua komponen untuk jenjang + tahun pelajaran tertentu. */
    public static function totalBobot(string $jenjang, string $tahunPelajaran): int
    {
        return (int) static::where('jenjang', $jenjang)
            ->where('tahun_pelajaran', $tahunPelajaran)
            ->sum('bobot');
    }
}
