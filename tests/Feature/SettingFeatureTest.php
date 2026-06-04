<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class SettingFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_settings_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/settings');

        $response->assertStatus(200);
    }

    public function test_health_endpoint_is_accessible(): void
    {
        $response = $this->get('/api/health');

        $response->assertStatus(200);
    }

    public function test_system_status_page_is_accessible(): void
    {
        $response = $this->get('/status');

        $response->assertStatus(200);
    }

    public function test_welcome_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
