<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormulirController extends Controller
{
    /**
     * Halaman pembuka pulau Formulir K3S.
     * Batch 1 hanya fondasi: builder & halaman pengisian menyusul.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sekolah = $user?->ptk?->sekolah;

        return Inertia::render('Formulir/Index', [
            'user' => [
                'name' => $user->name,
                'user_name' => $user->user_name,
                'roles' => $user->getRoleNames(),
            ],
            'isAdmin' => $user->hasRole('Admin'),
            'sekolah' => $sekolah ? [
                'id' => $sekolah->id,
                'npsn' => $sekolah->npsn,
                'nama' => $sekolah->detailTerbaru?->nama_sekolah,
                'jenjang' => $sekolah->jenjang,
            ] : null,
        ]);
    }
}
