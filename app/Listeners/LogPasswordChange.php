<?php

namespace App\Listeners;

use App\Models\ActivityLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogPasswordChange implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        ActivityLog::create([
            'user_id'    => $user->id,
            'action'     => 'password_change',
            'description' => "Password changed for user: {$user->email}",
            'subject_type' => 'User',
            'subject_id'   => $user->id,
            'properties'   => [
                'email'     => $user->email,
                'ip'        => request()->ip(),
                'user_agent'=> request()->userAgent(),
                'timestamp' => now()->toISOString(),
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
