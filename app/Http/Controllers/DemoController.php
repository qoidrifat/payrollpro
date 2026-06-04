<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SecurityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Demo login controller — restricted to local/testing environment only.
 *
 * The @see LocalOnlyMiddleware and @see RouteServiceProvider::boot()
 * prevent these routes from being registered or reached in production.
 */
class DemoController extends Controller
{
    /**
     * Create or log in as the demo user with view-only permissions.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function login(Request $request)
    {
        abort_unless(
            App::environment('local', 'testing'),
            403,
            'Akses demo dinonaktifkan di environment saat ini.'
        );

        $demoUser = User::firstOrCreate(
            ['email' => config('demo.email')],
            [
                'name'              => config('demo.name'),
                'password'          => bcrypt(config('demo.password')),
                'email_verified_at' => now(),
            ]
        );

        // Ensure demo role exists with minimal view-only permissions
        $demoRole = Role::firstOrCreate(['name' => 'Demo']);
        $demoRole->syncPermissions([
            'view-attendance',
            'view-payroll',
            'view-dashboard',
            'view-reports',
        ]);

        // Assign restricted demo role — never grant Admin or HR
        $demoUser->syncRoles('Demo');

        Auth::login($demoUser, true);

        $request->session()->regenerate();

        SecurityLogger::log('demo_login', [
            'user_id'    => $demoUser->id,
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Selamat datang di demo! Anda masuk dengan akses lihat saja.');
    }
}
