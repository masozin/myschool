<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class SekolahScope implements Scope
{
  public function apply(Builder $builder, Model $model)
  {
    // Pastikan ada user yang login
    if (Auth::check()) {
      $user = Auth::user();

      // Admin tidak difilter ke satu sekolah. Model User memakai Spatie,
      // jadi pengecekan harus lewat hasRole('Admin') — atribut $user->role
      // tidak ada dan selalu null, sehingga admin yang terhubung PTK
      // ikut terfilter sebelumnya.
      if (! $user->hasRole('Admin')) {

        // Ambil sekolah_id dari data PTK yang terhubung dengan user
        // Kita asumsikan user memiliki relasi ke ptk
        $sekolahId = $user->ptk->sekolah_id ?? null;

        if ($sekolahId) {
          // WAJIB dikualifikasi nama tabel modelnya: kolom tanpa nama
          // tabel membuat query yang men-JOIN tabel lain pemilik kolom
          // sekolah_id juga (mis. tabel_rombel_anggota) gagal dengan
          // "Column 'sekolah_id' in where clause is ambiguous" (1052).
          $builder->where($model->getTable() . '.sekolah_id', $sekolahId);
        }
      }
    }
  }
}
