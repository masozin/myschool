<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
  public function toResponse($request)
  {
    // Simpan semester_id yang dipilih user ke session
    if ($request->filled('semester_id')) {
      $semesterId = $request->integer('semester_id');

      // Validasi: semester harus ada di database
      $semester = \App\Models\Semester::find($semesterId);
      if ($semester) {
        session(['semester_id' => $semester->id]);
      }
    }

    // Jika tidak ada semester dipilih, fallback ke semester aktif
    if (!session()->has('semester_id')) {
      $semester = \App\Models\Semester::where('is_active', 1)->latest()->first()
        ?? \App\Models\Semester::latest()->first();

      if ($semester) {
        session(['semester_id' => $semester->id]);
      }
    }

    return redirect()->intended(config('fortify.home'));
  }
}
