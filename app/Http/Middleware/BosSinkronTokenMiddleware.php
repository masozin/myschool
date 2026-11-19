<?php

namespace App\Http\Middleware;

use App\Models\BosSinkronToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Otentikasi agen sinkron Kelola BOS.
 *
 * Agen mengirim token sekolahnya lewat header
 * `Authorization: Bearer <token>` (atau `X-Bos-Token`).
 * Yang dibandingkan adalah hash sha256 token terhadap
 * bos_sinkron_token; sekolah yang cocok ditempelkan ke
 * request sebagai atribut `bos_sekolah_id` dan semua
 * upsert sinkron dibatasi ke sekolah itu.
 */
class BosSinkronTokenMiddleware
{
  public function handle(Request $request, Closure $next): Response
  {
    $token = $request->bearerToken() ?: $request->header('X-Bos-Token');

    if (!$token) {
      return response()->json(['message' => 'Token sinkron tidak dikirim.'], 401);
    }

    $baris = BosSinkronToken::where('token_hash', hash('sha256', $token))->first();

    if (!$baris) {
      return response()->json(['message' => 'Token sinkron tidak dikenal.'], 401);
    }

    $baris->forceFill(['terakhir_dipakai' => now()])->saveQuietly();
    $request->attributes->set('bos_sekolah_id', (int) $baris->sekolah_id);

    return $next($request);
  }
}
