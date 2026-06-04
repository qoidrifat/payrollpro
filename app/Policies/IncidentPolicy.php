<?php

namespace App\Policies;

use App\Models\User;

class IncidentPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public
    }

    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'HR']);
    }
}
