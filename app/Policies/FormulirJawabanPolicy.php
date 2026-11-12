<?php
// app/Policies/FormulirJawabanPolicy.php

namespace App\Policies;

use App\Models\FormulirJawaban;
use App\Models\User;

class FormulirJawabanPolicy
{
    public function update(User $user, FormulirJawaban $submission): bool
    {
        return $submission->user_id === $user->id;
    }

    public function view(User $user, FormulirJawaban $submission): bool
    {
        return $submission->user_id === $user->id;
    }
}
