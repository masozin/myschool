<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * Root view untuk pulau Inertia modul Formulir.
     * Halaman Blade/Materialize myschool tidak memakai view ini.
     */
    protected $rootView = 'formulir.app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     * Pengguna yang dibagikan adalah pengguna myschool (login yang
     * sama), lengkap dengan role Spatie dan sekolah dari PTK-nya.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'user_name' => $user->user_name,
                    'email' => $user->email,
                    'roles' => $user->getRoleNames(),
                    'sekolah_id' => $user->ptk?->sekolah_id,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'success_submission' => fn () => $request->session()->get('success_submission'),
            ],
        ];
    }
}
