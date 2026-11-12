<?php

namespace App\Http\Controllers;

use App\Models\Formulir;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormulirController extends Controller
{
    /**
     * Dashboard pengisi pulau Formulir K3S.
     * Menampilkan form yang sedang terbuka untuk pengguna ini:
     * form global (sekolah_id null) atau form khusus sekolahnya,
     * yang menargetkan role-nya. Admin melihat semua form terbuka.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $sekolah = $user?->ptk?->sekolah;
        $isAdmin = $user->hasRole('Admin');

        $submittedFormIds = $user->jawabanFormulir()->pluck('form_id')->unique();

        $query = Formulir::published()
            ->with('sekolah.detailTerbaru');

        if (!$isAdmin) {
            $sekolahId = $user->ptk?->sekolah_id;

            $query->where(function ($q) use ($sekolahId) {
                $q->whereNull('sekolah_id');

                if ($sekolahId) {
                    $q->orWhere('sekolah_id', $sekolahId);
                }
            });
        }

        $activeForms = $query->get()
            ->filter->isOpen()
            ->filter(fn ($form) => $isAdmin || $form->isTargetedAtUser($user))
            ->map(fn ($form) => [
                'id' => $form->id,
                'title' => $form->title,
                'slug' => $form->slug,
                'description' => $form->description,
                'end_at' => $form->end_at,
                'allow_multiple_submissions' => $form->allow_multiple_submissions,
                'has_submitted' => $submittedFormIds->contains($form->id),
                'sekolah_nama' => $form->sekolah?->detailTerbaru?->nama_sekolah,
            ])
            ->values();

        return Inertia::render('Formulir/Index', [
            'user' => [
                'name' => $user->name,
                'user_name' => $user->user_name,
                'roles' => $user->getRoleNames(),
            ],
            'isAdmin' => $isAdmin,
            'sekolah' => $sekolah ? [
                'id' => $sekolah->id,
                'npsn' => $sekolah->npsn,
                'nama' => $sekolah->detailTerbaru?->nama_sekolah,
                'jenjang' => $sekolah->jenjang,
            ] : null,
            'activeForms' => $activeForms,
            'stats' => [
                'forms_active' => $activeForms->count(),
                'forms_submitted' => $activeForms->where('has_submitted', true)->count(),
                'forms_pending' => $activeForms->where('has_submitted', false)->count(),
            ],
        ]);
    }
}
