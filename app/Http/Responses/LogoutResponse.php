<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class LogoutResponse implements LogoutResponseContract
{
  public function toResponse($request)
  {
    // Redirect ke halaman login setelah logout
    return redirect()->route('login');
  }
}
