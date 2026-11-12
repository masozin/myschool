<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class Formulir extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tabel_formulir';

    public const STATUS_DRAFT     = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_CLOSED    = 'closed';

    public const TARGET_ALL = 'semua';

    protected $fillable = [
        'sekolah_id',
        'created_by',
        'title',
        'slug',
        'description',
        'banner_image',
        'start_at',
        'end_at',
        'status',
        'allow_multiple_submissions',
        'require_login',
        'target_role',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'allow_multiple_submissions' => 'boolean',
            'require_login' => 'boolean',
        ];
    }

    public function needsLogin(): bool
    {
        return $this->require_login;
    }

    /**
     * Apakah formulir ini ditargetkan ke pengguna myschool tertentu.
     * Nilai target_role mengikuti form-k3s (semua/operator/guru/
     * kepala_sekolah/murid) dan dicocokkan ke role Spatie / relasi
     * pengguna myschool.
     */
    public function isTargetedAtUser(User $user): bool
    {
        if (!$this->target_role || $this->target_role === self::TARGET_ALL) {
            return true;
        }

        return match ($this->target_role) {
            'operator'       => $user->hasRole('Operator Sekolah'),
            'guru'           => $user->hasRole('Guru'),
            'kepala_sekolah' => $user->hasRole('Kepala Sekolah'),
            'murid'          => $user->Siswa !== null,
            default          => false,
        };
    }

    // Jawaban milik user tertentu, untuk daftar riwayat
    public function submissionsBy(int $userId)
    {
        return $this->submissions()->where('user_id', $userId)->latest();
    }

    protected static function booted(): void
    {
        static::creating(function (Formulir $form) {
            if (empty($form->slug)) {
                $form->slug = static::generateUniqueSlug($form->title);
            }
        });
    }

    public static function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }

    // ---- Relasi ----

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class, 'sekolah_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(FormulirBagian::class, 'form_id')->orderBy('sort_order');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormulirField::class, 'form_id')->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormulirJawaban::class, 'form_id');
    }

    // ---- Scopes ----

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeForSchool($query, int $sekolahId)
    {
        return $query->where('sekolah_id', $sekolahId);
    }

    // ---- Business logic ----

    public function isOpen(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        $now = Carbon::now();

        if ($this->start_at && $now->lt($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * Alasan kenapa form tidak bisa diisi saat ini, atau null kalau form terbuka.
     * Dipakai untuk menampilkan pesan yang tepat di halaman publik.
     */
    public function closedReason(): ?string
    {
        if ($this->status === self::STATUS_DRAFT) {
            return 'not_published';
        }

        if ($this->status === self::STATUS_CLOSED) {
            return 'closed';
        }

        $now = Carbon::now();

        if ($this->start_at && $now->lt($this->start_at)) {
            return 'not_started';
        }

        if ($this->end_at && $now->gt($this->end_at)) {
            return 'ended';
        }

        return null; // terbuka
    }

    public function hasSubmittedBy(?int $userId): bool
    {
        if (!$userId || $this->allow_multiple_submissions) {
            return false;
        }

        return $this->submissions()->where('user_id', $userId)->exists();
    }

    public function submissionsCount(): int
    {
        return $this->submissions()->count();
    }
}
