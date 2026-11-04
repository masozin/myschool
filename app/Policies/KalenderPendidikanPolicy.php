<?php

namespace App\Policies;

use App\Models\KalenderPendidikan;
use App\Models\User;

class KalenderPendidikanPolicy
{
  /**
   * NOTE: asumsi kamu pakai package Spatie Laravel-Permission (method hasRole()
   * tersedia di model User lewat trait HasRoles), karena itu yang paling umum
   * dipakai di starter kit seperti ini. Kalau role management-mu berbeda
   * (mis. kolom `role` string biasa di tabel users), tinggal ganti baris
   * `$user->hasRole('Admin')` di bawah jadi `$user->role === 'Admin'`.
   */

  /**
   * Semua user yang login boleh melihat daftar kalender pendidikan.
   */
  public function viewAny(User $user): bool
  {
    return true;
  }

  public function view(User $user, KalenderPendidikan $kalenderPendidikan): bool
  {
    return true;
  }

  /**
   * Hanya role Admin yang boleh menambah kegiatan baru.
   */
  public function create(User $user): bool
  {
    return $user->hasRole('Admin');
  }

  /**
   * Hanya role Admin yang boleh mengubah kegiatan.
   */
  public function update(User $user, KalenderPendidikan $kalenderPendidikan): bool
  {
    return $user->hasRole('Admin');
  }

  /**
   * Hanya role Admin yang boleh menghapus kegiatan.
   */
  public function delete(User $user, KalenderPendidikan $kalenderPendidikan): bool
  {
    return $user->hasRole('Admin');
  }
}
