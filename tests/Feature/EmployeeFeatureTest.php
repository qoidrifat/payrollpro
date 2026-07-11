<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class EmployeeFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_employee_index_page_is_accessible(): void
    {
        Employee::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get('/employees');

        $response->assertStatus(200);
    }

    public function test_employee_index_shows_employees(): void
    {
        Employee::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        Employee::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        $response = $this->actingAs($this->admin)->get('/employees');

        $response->assertStatus(200);
    }

    public function test_employee_creation_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/employees/create');

        $response->assertStatus(200);
    }

    public function test_employee_can_be_created(): void
    {
        $employeeData = [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'nik' => '1234567890123456',
            'gender' => 'male',
            'position' => 'Developer',
            'department' => 'IT',
            'join_date' => '2026-01-15',
            'employment_status' => 'permanent',
            'base_salary' => 5000000,
        ];

        $response = $this->actingAs($this->admin)->post('/employees', $employeeData);

        $response->assertSessionHasNoErrors();
        $employee = Employee::where('first_name', 'Budi')->firstOrFail();

        $this->assertSame('1234567890123456', $employee->nik);
        $this->assertSame(Employee::hashNik('1234567890123456'), $employee->nik_hash);
    }

    public function test_audit_log_redacts_sensitive_pii_fields(): void
    {
        $employee = Employee::factory()->create([
            'nik' => '1234567890123456',
            'npwp' => '09.254.294.3-407.000',
            'bank_account_number' => '1122334455',
        ]);

        $log = ActivityLog::where('subject_type', Employee::class)
            ->where('subject_id', $employee->id)
            ->where('action', 'created')
            ->firstOrFail();

        $after = $log->properties['after'];

        $this->assertSame('[redacted]', $after['nik']);
        $this->assertSame('[redacted]', $after['npwp']);
        $this->assertSame('[redacted]', $after['bank_account_number']);
        $this->assertSame('[redacted]', $after['nik_hash']);
        // Non-sensitif tetap tersimpan apa adanya.
        $this->assertSame($employee->first_name, $after['first_name']);
        // Nilai mentah PII tidak boleh muncul di mana pun pada log.
        $this->assertStringNotContainsString('1234567890123456', json_encode($log->properties));
        $this->assertStringNotContainsString('1122334455', json_encode($log->properties));
    }

    public function test_duplicate_nik_is_rejected_using_blind_index(): void
    {
        Employee::factory()->create(['nik' => '1234567890123456']);

        $response = $this->actingAs($this->admin)->post('/employees', [
            'first_name' => 'Duplicate',
            'last_name' => 'Nik',
            'nik' => '1234567890123456',
            'gender' => 'male',
            'position' => 'Developer',
            'department' => 'IT',
            'join_date' => '2026-01-15',
            'employment_status' => 'permanent',
            'base_salary' => 5000000,
        ]);

        $response->assertSessionHasErrors('nik');
    }

    public function test_employee_can_be_viewed(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->admin)->get("/employees/{$employee->id}");

        $response->assertStatus(200);
    }

    public function test_employee_can_be_updated(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Original',
        ]);

        $response = $this->actingAs($this->admin)->put("/employees/{$employee->id}", [
            'first_name' => 'Updated',
            'last_name' => $employee->last_name,
            'nik' => $employee->nik,
            'gender' => $employee->gender,
            'position' => $employee->position,
            'department' => $employee->department,
            'join_date' => $employee->join_date->format('Y-m-d'),
            'employment_status' => $employee->employment_status->value,
            'base_salary' => $employee->base_salary,
            'is_active' => true,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'first_name' => 'Updated',
        ]);
    }

    public function test_employee_can_be_deleted(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/employees/{$employee->id}");

        $response->assertSessionHasNoErrors();

        // Employee uses SoftDeletes, so assert soft-deleted instead of missing
        $this->assertSoftDeleted($employee);
    }

    public function test_employee_list_can_be_searched(): void
    {
        Employee::factory()->create(['first_name' => 'SpecificName']);
        Employee::factory()->count(5)->create();

        $response = $this->actingAs($this->admin)->get('/employees?search=SpecificName');

        $response->assertStatus(200);
    }
}
