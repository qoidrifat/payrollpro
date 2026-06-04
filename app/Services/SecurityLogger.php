<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class SecurityLogger
{
    public static function log(string $event, array $context = []): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'user_id'    => $user?->id,
            'action'     => $event,
            'description'=> $event,
            'properties' => $context,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    public static function unauthorizedAccess(string $resource, array $extra = []): void
    {
        static::log('unauthorized_access', array_merge([
            'resource' => $resource,
            'url'      => request()?->fullUrl(),
            'method'   => request()?->method(),
        ], $extra));
    }

    public static function securityViolation(string $type, array $extra = []): void
    {
        static::log('security_violation', array_merge([
            'type' => $type,
            'url'  => request()?->fullUrl(),
        ], $extra));
    }
}
