<?php

namespace App\Http\Composers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SekolahComposer
{
  public function compose(View $view)
  {
    $sekolah = null;

    if (Auth::check() && Auth::user()->ptk) {
      $sekolah = Auth::user()->ptk->sekolah;
    }

    $view->with('sekolahGlobal', [
      'id'           => $sekolah->id ?? null,
      'nama_sekolah' => $sekolah->detailTerbaru->nama_sekolah ?? '-',
      'jenjang'      => strtolower($sekolah->jenjang ?? ''),
      'npsn'         => $sekolah->detailTerbaru->npsn ?? null,
    ]);
  }
}
