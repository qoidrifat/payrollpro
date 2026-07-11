<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicyMiddleware
{
    /**
     * Handle an incoming request.
     *
     * In production, strict CSP is applied.
     * In local/dev, Vite's dev server origin is added to allow HMR.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSP in local/dev environments to avoid blocking Vite HMR
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Generate a per-request nonce BEFORE the view renders. Laravel Vite
        // automatically stamps this nonce onto every @vite <script>/<style>
        // tag, and the Blade @routes directive receives it via Vite::cspNonce().
        // This lets us drop 'unsafe-inline'/'unsafe-eval' from script-src while
        // still allowing the few legitimate inline scripts (Ziggy routes, Vite
        // module loader) to execute.
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $csp = "default-src 'self'; "
            . "script-src 'self' 'nonce-{$nonce}'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com data:; "
            . "img-src 'self' data: blob:; "
            . "connect-src 'self' ws: wss:; "
            . "frame-src 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "form-action 'self';";

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        return $response;
    }
}
