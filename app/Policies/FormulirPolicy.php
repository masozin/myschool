<?php

namespace App\Policies;

use App\Models\Formulir;
use App\Models\User;

class FormulirPolicy
{
    // Builder formulir hanya untuk Admin myschool (role Spatie).
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    public function view(User $user, Formulir $formulir): bool
    {
        return $user->hasRole('Admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin');
    }

    public function update(User $user, Formulir $formulir): bool
    {
        return $user->hasRole('Admin');
    }

    public function delete(User $user, Formulir $formulir): bool
    {
        return $user->hasRole('Admin');
    }
}
