<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage-attendance', 'view-attendance']);
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasAnyPermission(['manage-attendance', 'view-attendance']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-attendance');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('manage-attendance');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermissionTo('manage-attendance');
    }
}
