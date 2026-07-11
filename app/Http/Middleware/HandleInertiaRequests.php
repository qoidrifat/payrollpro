<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? $this->serializeUser($user) : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Serialize user data with caching for roles & permissions.
     * Split into smaller cache keys so individual items expire independently.
     * Only include permissions if the user is an Admin (reduces payload for Employees).
     */
    private function serializeUser($user): array
    {
        $base = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'account_status' => $user->account_status,
        ];

        $hasEmployeeRecord = Cache::remember(
            "inertia:user-has-employee:{$user->id}",
            600,
            fn () => $user->employee()->exists()
        );

        $roles = Cache::remember(
            "inertia:user-roles:{$user->id}",
            600,
            fn () => $user->getRoleNames()->values()->all()
        );

        // Only load permissions cache for Admin/HR users (not for Employees)
        $isAdminOrHr = ! empty(array_intersect($roles, ['Admin', 'HR']));

        if ($isAdminOrHr) {
            $permissions = Cache::remember(
                "inertia:user-permissions:{$user->id}",
                600,
                fn () => $user->getAllPermissions()->pluck('name')->values()->all()
            );
        } else {
            $permissions = [];
        }

        return $base + [
            'has_employee_record' => $hasEmployeeRecord,
            'roles' => $roles,
            'permissions' => $permissions,
        ];
    }
}
