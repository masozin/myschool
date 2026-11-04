<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Exceptions\FiturNonaktifException;

class CheckFitur
{
  /**
   * Handle an incoming request.
   *
   * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
   */
  public function handle(Request $request, Closure $next, string $kode)
  {
    if (!\App\Helpers\FiturHelper::punya($kode)) {
      throw new FiturNonaktifException($kode);
    }

    return $next($request);
  }
}
