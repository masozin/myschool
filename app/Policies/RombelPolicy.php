<?php

namespace App\Policies;

use App\Models\Rombel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RombelPolicy
{
  /**
   * Determine whether the user can view any models.
   */
  public function viewAny(User $user): bool
  {
    return $user->hasRole('Admin');
  }

  /**
   * Determine whether the user can view the model.
   */
  public function view(User $user, Rombel $rombel): bool
  {
    // Logika yang sama dengan update atau bisa disesuaikan
    return $user->hasRole(['Admin', 'Operator Sekolah', 'Kepala Sekolah', 'Guru Kelas'])
      || ($user->ptk && $user->ptk->id === $rombel->ptk_id);
  }

  /**
   * Determine whether the user can create models.
   */
  public function create(User $user): bool
  {
    return $user->hasRole(['Admin', 'Operator Sekolah']);
  }

  /**
   * Determine whether the user can update the model.
   */
  public function update(User $user, Rombel $rombel): bool
  {
    // 1. Jika User adalah Admin atau Operator, izinkan semua (Super User)
    if ($user->hasRole(['Admin', 'Operator Sekolah'])) {
      return true;
    }

    // 2. Jika User adalah Guru, cek apakah ID PTK-nya sama dengan wali_kelas di rombel ini
    return $user->ptk && $user->ptk->id === $rombel->ptk_id;
  }

  /**
   * Determine whether the user can delete the model.
   */
  public function delete(User $user, Rombel $rombel): bool
  {
    return $user->hasRole(['Admin', 'Operator Sekolah']);
  }

  /**
   * Determine whether the user can restore the model.
   */
  public function restore(User $user, Rombel $rombel): bool
  {
    return false;
  }

  /**
   * Determine whether the user can permanently delete the model.
   */
  public function forceDelete(User $user, Rombel $rombel): bool
  {
    return false;
  }

  public function manageMembers(User $user, Rombel $rombel)
  {
    // Admin dan Operator bisa kelola anggota rombel mana saja
    if ($user->hasRole(['Admin', 'Operator Sekolah'])) {
      return true;
    }

    // Wali Kelas HANYA bisa kelola anggota di rombel miliknya sendiri
    return $user->ptk && $user->ptk->id === $rombel->ptk_id;
  }
}
