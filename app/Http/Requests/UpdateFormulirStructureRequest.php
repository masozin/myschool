<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFormulirStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('Admin');
    }

    public function rules(): array
    {
        return [
            'sections' => ['array'],
            'sections.*.id' => ['nullable', 'integer'],
            'sections.*.title' => ['required', 'string', 'max:255'],
            'sections.*.description' => ['nullable', 'string'],
            'sections.*.sort_order' => ['required', 'integer'],

            'sections.*.fields' => ['array'],
            'sections.*.fields.*.id' => ['nullable', 'integer'],
            'sections.*.fields.*.type' => ['required', 'string', 'in:text,textarea,number,select,radio,checkbox,date,email,file,heading,paragraph'],
            'sections.*.fields.*.name' => ['required', 'string', 'regex:/^[a-z0-9_]+$/'],
            'sections.*.fields.*.label' => ['required', 'string', 'max:255'],
            'sections.*.fields.*.description' => ['nullable', 'string'],
            'sections.*.fields.*.config' => ['nullable', 'array'],
            'sections.*.fields.*.is_required' => ['boolean'],
            'sections.*.fields.*.sort_order' => ['required', 'integer'],

            // Field tanpa section (langsung di form)
            'fields' => ['array'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.type' => ['required', 'string'],
            'fields.*.name' => ['nullable', 'string'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.config' => ['nullable', 'array'],
            'fields.*.is_required' => ['boolean'],
            'fields.*.sort_order' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'sections.*.fields.*.name.regex' => 'Nama internal field hanya boleh huruf kecil, angka, dan underscore.',
        ];
    }
}
