<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormulirBagian extends Model
{
    use HasFactory;

    protected $table = 'tabel_formulir_bagian';

    protected $fillable = [
        'form_id',
        'title',
        'description',
        'sort_order',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Formulir::class, 'form_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormulirField::class, 'section_id')->orderBy('sort_order');
    }
}
