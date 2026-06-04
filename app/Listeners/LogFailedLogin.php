<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class LogFailedLogin implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? 'unknown';

        ActivityLog::create([
            'user_id'    => $event->user?->id,
            'action'     => 'login_failed',
            'description' => "Failed login attempt for email: {$email} from IP: " . request()->ip(),
            'subject_type' => 'Login',
            'subject_id'   => null,
            'properties'   => [
                'email'    => $email,
                'ip'       => request()->ip(),
                'user_agent' => request()->userAgent(),
                'timestamp'  => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::warning('Failed login attempt', [
            'email' => $email,
            'ip'    => request()->ip(),
        ]);
    }
}
