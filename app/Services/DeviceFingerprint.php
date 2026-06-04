<?php

namespace App\Services;

use Illuminate\Http\Request;

class DeviceFingerprint
{
    /**
     * Build a lightweight device fingerprint from the current request.
     */
    public static function get(Request $request = null): string
    {
        $request ??= request();

        $components = [
            $request->ip(),
            $request->userAgent(),
            $request->header('Accept-Language', ''),
            $request->header('Accept-Encoding', ''),
        ];

        return hash('sha256', implode('|', $components));
    }

    /**
     * Return the fingerprint context as an array for logging.
     */
    public static function context(Request $request = null): array
    {
        $request ??= request();

        return [
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'fingerprint' => static::get($request),
            'language'    => $request->header('Accept-Language'),
        ];
    }
}
