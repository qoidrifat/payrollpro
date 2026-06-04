<?php

namespace App\Policies;

use App\Models\User;

class SystemServicePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Public status page
    }

    public function manage(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'HR']);
    }
}
