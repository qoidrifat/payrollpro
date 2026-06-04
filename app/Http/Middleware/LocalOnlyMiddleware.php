<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalOnlyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            abort(403, 'Demo access is disabled in production.');
        }

        return $next($request);
    }
}