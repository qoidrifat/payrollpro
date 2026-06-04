<?php

namespace App\Policies;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['manage-payroll', 'view-payroll']);
    }

    public function view(User $user, Payroll $payroll): bool
    {
        return $user->hasAnyPermission(['manage-payroll', 'view-payroll']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('manage-payroll');
    }

    public function update(User $user, Payroll $payroll): bool
    {
        return $user->hasPermissionTo('manage-payroll') && $payroll->status === PayrollStatus::Draft;
    }

    public function approve(User $user, Payroll $payroll): bool
    {
        return $user->hasPermissionTo('manage-payroll') && $payroll->status === PayrollStatus::Processed;
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->hasPermissionTo('manage-payroll') && $payroll->status === PayrollStatus::Draft;
    }
}
