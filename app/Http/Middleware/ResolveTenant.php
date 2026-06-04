<?php

namespace App\Http\Middleware;

use App\Services\CompanyService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->companyService->register();

        return $next($request);
    }
}
