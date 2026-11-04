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

      // Jika user adalah 'operator' atau 'guru', filter datanya
      // Sesuaikan dengan logic role di sistem Anda
      if ($user->role !== 'admin') {

        // Ambil sekolah_id dari data PTK yang terhubung dengan user
        // Kita asumsikan user memiliki relasi ke ptk
        $sekolahId = $user->ptk->sekolah_id ?? null;

        if ($sekolahId) {
          $builder->where('sekolah_id', $sekolahId);
        }
      }
    }
  }
}
