<?php

/**
 * Demo access configuration.
 *
 * Demo account credentials are stored here (not hardcoded in the controller)
 * so they can be changed per environment without touching application code.
 */
return [

    'email' => env('DEMO_EMAIL'),
    'password' => env('DEMO_PASSWORD'),
    'name' => env('DEMO_NAME', 'Demo User'),

    'rate_limit' => [
        'attempts' => (int) env('DEMO_RATE_LIMIT', 3),
        'per_minutes' => (int) env('DEMO_RATE_PER_MINUTES', 1),
    ],

];
