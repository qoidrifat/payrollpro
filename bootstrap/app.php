<?php

use App\Http\Middleware\ContentSecurityPolicyMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\LocalOnlyMiddleware;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\ValidateSignedEmployeeRoute;
use App\Repositories\AttendanceRepositoryInterface;
use App\Repositories\Eloquent\EloquentAttendanceRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentPayrollRepository;
use App\Repositories\EmployeeRepositoryInterface;
use App\Repositories\PayrollRepositoryInterface;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ResolveTenant::class,
            HandleInertiaRequests::class,
            ContentSecurityPolicyMiddleware::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'local_only'      => LocalOnlyMiddleware::class,
            'signed.employee' => ValidateSignedEmployeeRoute::class,
            'tenant'          => ResolveTenant::class,
            'role'            => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
