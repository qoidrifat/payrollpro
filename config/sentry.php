<?php

return [

    /*
    |--------------------------------------------------------------------------
    | DSN
    |--------------------------------------------------------------------------
    |
    | The Sentry DSN (Data Source Name) is the key that connects your
    | application to your Sentry project. Set it in your .env file.
    |
    | Example: SENTRY_LARAVEL_DSN=https://examplePublicKey@o0.ingest.sentry.io/0
    |
    */

    'dsn' => env('SENTRY_LARAVEL_DSN'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | This is the environment name Sentry will use. You can set this to
    | 'production', 'staging', 'local', etc. Defaults to APP_ENV.
    |
    */

    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),

    /*
    |--------------------------------------------------------------------------
    | Release
    |--------------------------------------------------------------------------
    |
    | This is the version of your application that Sentry will associate
    | with the errors. Useful for tracking which release introduced a bug.
    |
    | Can be a git commit hash, tag, or any string.
    |
    */

    'release' => env('SENTRY_RELEASE'),

    /*
    |--------------------------------------------------------------------------
    | Sample Rate
    |--------------------------------------------------------------------------
    |
    | This is the percentage of transactions to send to Sentry.
    | 1.0 = 100% (all), 0.1 = 10%. Set to 0.0 to disable performance tracing.
    |
    */

    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),

    /*
    |--------------------------------------------------------------------------
    | Traces Sample Rate
    |--------------------------------------------------------------------------
    |
    | This is the percentage of requests that should be monitored for
    | performance tracing. 1.0 = 100% (all), 0.1 = 10%.
    |
    */

    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Profiles Sample Rate
    |--------------------------------------------------------------------------
    |
    | This is the percentage of transactions that should be profiled.
    | 1.0 = 100% (all), 0.1 = 10%.
    |
    */

    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.1),

    /*
    |--------------------------------------------------------------------------
    | Breadcrumb Queries
    |--------------------------------------------------------------------------
    |
    | When set to true, database queries will be recorded as breadcrumbs.
    | Useful for debugging, but may generate large volumes of data.
    |
    */

    'breadcrumbs' => [
        'sql_queries' => env('SENTRY_BREADCRUMBS_SQL_QUERIES', false),
        'sql_bindings' => env('SENTRY_BREADCRUMBS_SQL_BINDINGS', false),
        'redis' => env('SENTRY_BREADCRUMBS_REDIS', true),
        'queue_info' => env('SENTRY_BREADCRUMBS_QUEUE_INFO', true),
        'logs' => env('SENTRY_BREADCRUMBS_LOGS', true),
        'http_client_requests' => env('SENTRY_BREADCRUMBS_HTTP_CLIENT_REQUESTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | User Context
    |--------------------------------------------------------------------------
    |
    | When enabled, Sentry will automatically attach authenticated user
    | information to error reports (ID, email, username).
    |
    */

    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

];
