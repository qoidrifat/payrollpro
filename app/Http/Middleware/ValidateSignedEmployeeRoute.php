<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use App\Services\SecurityLogger;

class ValidateSignedEmployeeRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->route('employee');

        if (!$employee instanceof Employee) {
            SecurityLogger::unauthorizedAccess('signed_employee_route', [
                'reason' => 'employee_not_resolved',
            ]);
            abort(403, 'Invalid employee route.');
        }

        $user = Auth::user();

        if (!$user) {
            SecurityLogger::unauthorizedAccess('signed_employee_route', [
                'employee_id' => $employee->id,
                'reason'      => 'unauthenticated',
            ]);
            abort(403, 'Authentication required.');
        }

        // Admin/HR can access any employee route
        if ($user->hasAnyRole(['Admin', 'HR'])) {
            return $next($request);
        }

        // Employees can only access their own QR routes
        if ($user->employee?->id !== $employee->id) {
            SecurityLogger::unauthorizedAccess('signed_employee_route', [
                'employee_id' => $employee->id,
                'user_id'     => $user->id,
                'reason'      => 'employee_mismatch',
            ]);
            abort(403, 'You can only access your own attendance QR codes.');
        }

        return $next($request);
    }
}