<?php

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogPasswordChange;
use App\Models\ManualAttendanceRequest;
use App\Policies\ManualAttendanceRequestPolicy;
use App\Repositories\AttendanceRepositoryInterface;
use App\Repositories\Eloquent\EloquentAttendanceRepository;
use App\Repositories\Eloquent\EloquentEmployeeRepository;
use App\Repositories\Eloquent\EloquentPayrollRepository;
use App\Repositories\EmployeeRepositoryInterface;
use App\Repositories\PayrollRepositoryInterface;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ─── Force-disable Imagick for Dompdf ───────────────────────────
        // Prevents Windows RegistryKeyLookupFailed error in CPDF backend.
        // barryvdh/laravel-dompdf's defines array is converted to Options
        // (not PHP constants), so Dompdf falls through to extension_loaded().
        // This define() MUST be in a service provider (not config/dompdf.php)
        // because config cache skips the original source's inline code.
        if (! defined('DOMPDF_ENABLE_IMAGICK')) {
            define('DOMPDF_ENABLE_IMAGICK', false);
        }

        // Repository Pattern Bindings
        $this->app->bind(EmployeeRepositoryInterface::class, EloquentEmployeeRepository::class);
        $this->app->bind(PayrollRepositoryInterface::class, EloquentPayrollRepository::class);
        $this->app->bind(AttendanceRepositoryInterface::class, EloquentAttendanceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::policy(ManualAttendanceRequest::class, ManualAttendanceRequestPolicy::class);

        // ─── Force HTTPS in production ───────────────────────────────
        // Render terminates SSL at edge, but Laravel needs explicit
        // scheme forcing so generated URLs (route(), asset(), etc.)
        // use https:// instead of http://.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Route-model binding for Laravel's native DatabaseNotification
        app('router')->bind('notification', function (string $value) {
            return DatabaseNotification::findOrFail($value);
        });

        // ─── Security: Event Listeners ────────────────────────────────
        // Log failed login attempts
        Event::listen(
            Failed::class,
            LogFailedLogin::class,
        );

        // Log password changes
        Event::listen(
            PasswordReset::class,
            LogPasswordChange::class,
        );

        // ─── Rate Limiters ────────────────────────────────────────────
        // Demo login: only 3 attempts per minute per IP
        RateLimiter::for('demo', function ($request) {
            return Limit::perMinute(
                config('demo.rate_limit.attempts', 3)
            )->by($request->ip());
        });

        // QR scan attendance: 10 per minute, scoped per employee + IP + user
        RateLimiter::for('qr-attendance', function ($request) {
            $employee = $request->route('employee');
            $employeeId = is_object($employee) ? $employee->id : $employee;

            return Limit::perMinute(10)->by(
                sha1($request->ip().$request->user()?->id.$employeeId)
            );
        });

        // API attendance endpoints: 60 per minute
        RateLimiter::for('api-attendance', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?? $request->ip());
        });
    }
}
