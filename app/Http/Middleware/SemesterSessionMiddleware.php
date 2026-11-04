<?php

namespace App\Http\Middleware;

use App\Models\Semester;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SemesterSessionMiddleware
{
  public function handle($request, Closure $next)
  {
    if (Auth::check() && !Session::has('semester_id')) {
      // Session expired tapi masih login — set ulang ke semester aktif
      $semester = Semester::where('is_active', 1)->latest()->first()
        ?? Semester::latest()->first();

      if ($semester) {
        Session::put('semester_id', $semester->id);
      }
    }

    return $next($request);
  }
}
