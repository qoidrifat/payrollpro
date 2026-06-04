<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-reports');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo('view-reports');
    }
}
