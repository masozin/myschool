<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormulirField extends Model
{
    use HasFactory;

    protected $table = 'tabel_formulir_field';

    // Tipe field yang butuh "options" (select/radio/checkbox)
    public const OPTION_TYPES = ['select', 'radio', 'checkbox'];

    // Tipe field yang sebenarnya bukan input (tidak masuk validasi/data)
    public const DISPLAY_ONLY_TYPES = ['heading', 'paragraph'];

    protected $fillable = [
        'form_id',
        'section_id',
        'type',
        'name',
        'label',
        'description',
        'config',
        'is_required',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_required' => 'boolean',
        ];
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Formulir::class, 'form_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(FormulirBagian::class, 'section_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(FormulirBerkas::class, 'field_id');
    }

    // ---- Helpers untuk baca config JSON dengan aman ----

    public function getOptions(): array
    {
        return $this->config['options'] ?? [];
    }

    public function getValidationConfig(): array
    {
        return $this->config['validation'] ?? [];
    }

    public function getPlaceholder(): ?string
    {
        return $this->config['placeholder'] ?? null;
    }

    public function getHelpText(): ?string
    {
        return $this->config['help_text'] ?? null;
    }

    public function getWidth(): string
    {
        return $this->config['appearance']['width'] ?? 'full';
    }

    public function isFileType(): bool
    {
        return $this->type === 'file';
    }

    public function isDisplayOnly(): bool
    {
        return in_array($this->type, self::DISPLAY_ONLY_TYPES);
    }

    public function needsOptions(): bool
    {
        return in_array($this->type, self::OPTION_TYPES);
    }
}
