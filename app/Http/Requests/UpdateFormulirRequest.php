<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormulirRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('Admin');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'banner_image' => ['nullable', 'image', 'max:2048'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['required', 'in:draft,published,closed'],
            'allow_multiple_submissions' => ['boolean'],
            'require_login' => ['boolean'],
            'target_role' => ['nullable', 'string', 'in:semua,operator,guru,kepala_sekolah,murid'],
            'school_id' => ['nullable', 'integer', 'exists:tabel_sekolah,id'],
        ];
    }
}
