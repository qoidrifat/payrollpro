<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic health check - the login page should be accessible.
     */
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * Authenticated users can access the dashboard.
     */
    public function test_dashboard_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Registration page should be accessible.
     */
    public function test_registration_page_is_accessible(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    /**
     * System health endpoint should return success.
     */
    public function test_health_endpoint_returns_success(): void
    {
        $response = $this->get('/api/health');
        $response->assertStatus(200);
    }

    /**
     * System status page should be accessible.
     */
    public function test_system_status_page_is_accessible(): void
    {
        $response = $this->get('/status');
        $response->assertStatus(200);
    }
}
