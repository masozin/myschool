<?php
// app/Http/Requests/JawabanFormulirRequest.php

namespace App\Http\Requests;

use App\Services\FormValidationRuleBuilder;
use Illuminate\Foundation\Http\FormRequest;

class JawabanFormulirRequest extends FormRequest
{
    public function authorize(): bool
    {
        $form = $this->route('formulir');
        $submission = $this->route('submission');

        // Pakai closedReason(), bukan isOpen(), supaya konsisten dengan controller
        if ($form->closedReason() !== null) {
            return false;
        }

        // Mode edit
        if ($submission) {
            // Guest submission (user_id null) diautentikasi lewat signed URL di route,
            // bukan lewat Gate::can — jadi izinkan kalau submission memang milik guest.
            if ($submission->user_id === null) {
                return true;
            }
            return $this->user()?->can('update', $submission) ?? false;
        }

        // Mode submit baru — cek submission ganda HANYA kalau form butuh login.
        // Guest tanpa login (user_id selalu null) tidak punya cara dicek "sudah pernah submit atau belum",
        // jadi untuk guest, lewati saja pengecekan ini.
        if ($form->needsLogin() && !$form->allow_multiple_submissions) {
            if ($form->hasSubmittedBy($this->user()?->id)) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        $builder = app(FormValidationRuleBuilder::class);
        $built = $builder->build($this->route('formulir'));
        $this->builtAttributes = $built['attributes'];
        return $built['rules'];
    }

    protected array $builtAttributes = [];

    public function attributes(): array
    {
        return $this->builtAttributes;
    }

    public function messages(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'email' => 'Kolom :attribute harus berupa email yang valid.',
            'numeric' => 'Kolom :attribute harus berupa angka.',
            'date' => 'Kolom :attribute harus berupa tanggal yang valid.',
            'in' => 'Pilihan pada :attribute tidak valid.',
            'file' => 'Kolom :attribute harus berupa file.',
            'mimes' => 'Kolom :attribute harus berformat: :values.',
            'max' => 'Kolom :attribute melebihi batas maksimum.',
            'min' => 'Kolom :attribute kurang dari batas minimum.',
        ];
    }
}
