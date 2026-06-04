<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected const EMPTY_EMPLOYEE_SCOPE = -1;

    /**
     * Check if the current user should have data scoped to their employee record.
     * Returns the employee ID for Employee-role users, null for Admin/HR.
     */
    protected function shouldScopeToEmployee(): bool
    {
        $user = request()->user();
        if (!$user) return false;
        if ($user->hasAnyRole(['Admin', 'HR'])) return false;
        return $user->hasRole('Employee');
    }

    /**
     * Get the current user's employee ID if they only have the Employee role.
     */
    protected function getEmployeeIdIfScoped(): ?int
    {
        if (!$this->shouldScopeToEmployee()) return null;
        return request()->user()->employee?->id ?? self::EMPTY_EMPLOYEE_SCOPE;
    }
}
