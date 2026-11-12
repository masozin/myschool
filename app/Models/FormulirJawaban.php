<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FormulirJawaban extends Model
{
    use HasFactory;

    protected $table = 'tabel_formulir_jawaban';

    protected $fillable = [
        'form_id',
        'user_id',
        'submission_token',
        'data',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FormulirJawaban $submission) {
            if (empty($submission->submission_token)) {
                $submission->submission_token = Str::uuid();
            }
        });
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Formulir::class, 'form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(FormulirBerkas::class, 'submission_id');
    }

    // Ambil jawaban satu field by name, dengan fallback null
    public function getAnswer(string $fieldName)
    {
        return $this->data[$fieldName] ?? null;
    }

    // Ambil file yang di-upload untuk field tertentu
    public function getFileFor(int $fieldId)
    {
        return $this->files()->where('field_id', $fieldId)->first();
    }

    // Gabungkan data JSON + file jadi satu array lengkap (untuk export/tampilan)
    public function getFullAnswers(): array
    {
        $answers = $this->data;

        foreach ($this->files as $file) {
            $fieldName = $file->field->name ?? "field_{$file->field_id}";
            $answers[$fieldName] = [
                'type' => 'file',
                'original_name' => $file->original_name,
                'url' => $file->url(),
            ];
        }

        return $answers;
    }
}
