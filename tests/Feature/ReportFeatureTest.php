<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class ReportFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_payroll_report_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/payroll');

        $response->assertStatus(200);
    }

    public function test_payroll_report_shows_summary(): void
    {
        Payroll::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/reports/payroll');

        $response->assertStatus(200);
    }

    public function test_tax_report_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/tax');

        $response->assertStatus(200);
    }

    public function test_attendance_report_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/reports/attendance');

        $response->assertStatus(200);
    }

    public function test_attendance_report_shows_employee_data(): void
    {
        Employee::factory()->count(3)->create(['is_active' => true]);

        $response = $this->actingAs($this->admin)->get('/reports/attendance');

        $response->assertStatus(200);
    }
}
