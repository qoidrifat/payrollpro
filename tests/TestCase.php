<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Routing\Middleware\ThrottleRequests;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // In Laravel 12 the CSRF middleware class is ValidateCsrfToken
        // (not VerifyCsrfToken as in earlier versions). Bypass it so
        // POST/PATCH/DELETE requests in feature tests don't get 419 errors.
        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Disable rate limiting in tests. Auth routes use `throttle:6,1`,
        // and the in-process array cache lets those counters accumulate
        // across the whole suite — producing order-dependent 429s that made
        // RegistrationTest / PasswordResetTest flaky on full runs. No test
        // asserts on throttling, so removing the middleware is safe.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /**
     * Creates the application and overrides database config to force
     * SQLite in-memory, ignoring whatever .env says about DB_CONNECTION.
     *
     * This runs before RefreshDatabase::refreshDatabase(), so isInMemory()
     * returns true and the test uses :memory: instead of hitting MySQL.
     */
    public function createApplication()
    {
        $app = parent::createApplication();

        // Force SQLite in-memory so DB-dependent tests work without MySQL
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');

        return $app;
    }
}
