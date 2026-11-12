<?php
// app/Http/Requests/JawabanFormulirRequest.php

namespace App\Http\Requests;

use App\Models\Formulir;
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

        // (Batch 4.5) Tegakkan aturan akses form di server — sebelumnya
        // aturan ini hanya diperiksa di lapisan tampil (show/create),
        // sehingga POST langsung bisa melewatinya.
        $this->enforceFormAccess($form);

        // Mode edit
        if ($submission) {
            // Guest submission (user_id null) diautentikasi lewat signed URL di route,
            // bukan lewat Gate::can — jadi izinkan kalau submission memang milik guest.
            if ($submission->user_id === null) {
                return true;
            }
            return $this->user()?->can('update', $submission) ?? false;
        }

        // Mode submit baru — cek submission ganda untuk SEMUA form
        // sekali-isi bila ada pengguna login, apa pun require_login-nya
        // (sebelumnya hanya dicek bila form mewajibkan login). Tamu tidak
        // punya identitas untuk dicek, jadi lewati.
        if ($form->hasSubmittedBy($this->user()?->id)) {
            return false;
        }

        return true;
    }

    /**
     * Aturan akses form, menyamai janji lapisan tampil
     * (FormulirPublikController::show/create) agar yang tampil boleh
     * juga boleh dikirim, dan sebaliknya:
     *
     * - Form wajib login: tidak ada jalur tamu sama sekali, termasuk
     *   edit tamu — kiriman tamu untuk form semacam itu memang tidak
     *   seharusnya ada.
     * - Form khusus satu sekolah (sekolah_id terisi): hanya pengguna
     *   login dari sekolah itu; tamu ditolak karena keanggotaan
     *   sekolahnya tidak dapat diverifikasi.
     * - Target role hanya dinilai untuk pengguna login — tamu lolos
     *   dari pemeriksaan ini, persis seperti show() yang melewatkan
     *   tamu (tamu tidak punya role untuk dicocokkan).
     */
    protected function enforceFormAccess(Formulir $form): void
    {
        $user = $this->user();

        if ($form->needsLogin() && ! $user) {
            abort(403, 'Formulir ini mewajibkan login. Silakan login terlebih dahulu untuk mengisi formulir ini.');
        }

        if ($form->sekolah_id) {
            if (! $user) {
                abort(403, 'Formulir ini hanya untuk sekolah tertentu. Silakan login dengan akun sekolah Anda untuk mengisinya.');
            }

            if ((int) ($user->ptk?->sekolah_id ?? 0) !== (int) $form->sekolah_id) {
                abort(403, 'Formulir ini ditujukan untuk sekolah lain.');
            }
        }

        if ($user && ! $form->isTargetedAtUser($user)) {
            abort(403, 'Formulir ini tidak ditujukan untuk Anda.');
        }
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
