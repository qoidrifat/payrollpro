<?php

namespace App\Policies;

use App\Models\ManualAttendanceRequest;
use App\Models\User;

class ManualAttendanceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'HR']);
    }

    public function view(User $user, ManualAttendanceRequest $manualAttendanceRequest): bool
    {
        if ($user->hasAnyRole(['Admin', 'HR'])) {
            return true;
        }

        return $user->employee?->id === $manualAttendanceRequest->employee_id;
    }

    public function create(User $user): bool
    {
        return $user->employee()->exists();
    }

    public function review(User $user, ManualAttendanceRequest $manualAttendanceRequest): bool
    {
        if (! $user->hasAnyRole(['Admin', 'HR'])) {
            return false;
        }

        // Reviewer (mis. HR yang juga karyawan) tidak boleh menyetujui atau
        // menolak pengajuan absen manualnya sendiri.
        return $user->employee?->id !== $manualAttendanceRequest->employee_id;
    }
}
