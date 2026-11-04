<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLearningModuleRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true; // sesuaikan dengan policy/role kamu nanti
  }

  public function rules(): array
  {
    return [
      'mapel_master_id'      => ['required', 'exists:tabel_mapel_master,id'],
      'semester_id'          => ['nullable', 'exists:tabel_semester,id'],
      'kurikulum_id'         => ['nullable', 'exists:tabel_kurikulum,id'],
      'judul_modul'          => ['nullable', 'string', 'max:255'],
      'bab'                  => ['nullable', 'string', 'max:100'],
      'materi_pokok'         => ['required', 'string', 'max:255'],
      'fase'                 => ['nullable', 'string', 'max:10'],
      'kelas'                => ['nullable', 'string', 'max:20'],
      'capaian_pembelajaran' => ['required', 'string'],
      'jumlah_jp'            => ['required', 'integer', 'min:1', 'max:100'],
      'jumlah_pertemuan'     => ['required', 'integer', 'min:1', 'max:50'],
      'model_pembelajaran'   => ['nullable', 'string', 'max:255'],
      'metode_pembelajaran'  => ['nullable', 'array'],
      'metode_pembelajaran.*' => ['string', 'max:100'],
      'target_peserta_didik' => ['nullable', 'string', 'max:255'],
    ];
  }
}
