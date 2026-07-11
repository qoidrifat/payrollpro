<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Attendance Operational Hours
    |--------------------------------------------------------------------------
    |
    | Existing QR attendance flow used 06:30-17:00 WIB. Keep the same window
    | here so page visibility, signed QR generation, scan pages, and manual
    | attendance requests share one configurable server-side rule.
    |
    */
    'operational_hours' => [
        'timezone' => env('ATTENDANCE_TIMEZONE', env('APP_TIMEZONE', 'Asia/Jakarta')),
        'start' => env('ATTENDANCE_OPERATIONAL_START', '06:30'),
        'end' => env('ATTENDANCE_OPERATIONAL_END', '17:00'),
    ],

    'qr' => [
        'refresh_interval_seconds' => env('ATTENDANCE_QR_REFRESH_INTERVAL_SECONDS', 300),
    ],

    /*
    |--------------------------------------------------------------------------
    | Offline Attendance Sync
    |--------------------------------------------------------------------------
    |
    | Offline records uploaded via the mobile syncOffline endpoint are only
    | accepted for dates within this backfill window (today .. today - N days),
    | never for future dates. This bounds how far back a device may backfill
    | attendance and complements the server-side geofence + status checks.
    |
    */
    'offline_sync' => [
        'max_backfill_days' => (int) env('ATTENDANCE_OFFLINE_MAX_BACKFILL_DAYS', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Manual Attendance Request
    |--------------------------------------------------------------------------
    |
    | Employee-submitted manual attendance claims may only cover dates within
    | this backfill window (today .. today - N days). Bounds retroactive claims
    | so an employee cannot submit an absence for, e.g., last year.
    |
    */
    'manual_request' => [
        'max_backfill_days' => (int) env('ATTENDANCE_MANUAL_MAX_BACKFILL_DAYS', 30),
    ],
];
