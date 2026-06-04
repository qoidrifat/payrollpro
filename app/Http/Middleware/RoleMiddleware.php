<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Authentication required.');
        }

        // Case-insensitive role check
        // Spatie's hasRole() is case-sensitive, but roles may be stored
        // with different casing (e.g. 'Admin' in DB vs 'admin' in middleware).
        //
        // Also split each role by '|' because Laravel's middleware parser
        // only splits parameters by comma, not by pipe. So 'role:admin|hr'
        // arrives as a single string 'admin|hr' instead of two strings.
        $userRoleNames = $user->getRoleNames()->map(fn($r) => strtolower($r));

        foreach ($roles as $role) {
            // Split by both comma and pipe to handle different route syntaxes
            foreach (explode('|', $role) as $singleRole) {
                foreach (explode(',', $singleRole) as $r) {
                    $r = trim(strtolower($r));
                    if ($r !== '' && $userRoleNames->contains($r)) {
                        return $next($request);
                    }
                }
            }
        }

        abort(403, 'Unauthorized. Required role: ' . implode('|', $roles));
    }
}
