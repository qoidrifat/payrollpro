<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeePortalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'Employee']);
        $this->employeeUser = User::factory()->create();
        $this->employeeUser->assignRole('Employee');
        $this->employee = Employee::factory()->create([
            'user_id' => $this->employeeUser->id,
        ]);
    }

    public function test_portal_dashboard_is_accessible(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/dashboard');

        $response->assertStatus(200);
    }

    public function test_portal_attendance_history_is_accessible(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/attendance');

        $response->assertStatus(200);
    }

    public function test_portal_payroll_history_is_accessible(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/payroll');

        $response->assertStatus(200);
    }

    public function test_portal_tax_info_is_accessible(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/tax');

        $response->assertStatus(200);
    }

    public function test_portal_leaves_page_is_accessible(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/leaves');

        $response->assertStatus(200);
    }

    public function test_portal_dashboard_shows_employee_data(): void
    {
        $response = $this->actingAs($this->employeeUser)->get('/portal/dashboard');

        $response->assertStatus(200);
        $response->assertSee($this->employee->first_name);
    }

    public function test_employee_without_employee_record_sees_dashboard_empty_state(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $response = $this->actingAs($user)->get('/portal/dashboard');

        $response->assertStatus(200);
    }

    public function test_employee_without_employee_record_is_guarded_from_portal_subpages(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');

        foreach (['/portal/attendance', '/portal/payroll', '/portal/tax', '/portal/leaves'] as $uri) {
            $this->actingAs($user)->get($uri)->assertForbidden();
        }
    }

    public function test_employee_without_employee_record_cannot_submit_leave_request(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $response = $this->actingAs($user)->post('/portal/leaves', [
            'leave_type' => 'annual',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Family matter',
        ]);

        $response->assertRedirect(route('portal.dashboard'));
        $response->assertSessionHas('error', 'Akun Anda belum terhubung dengan data karyawan.');
    }

    public function test_non_employee_role_cannot_access_portal_routes(): void
    {
        $user = User::factory()->create();
        Role::firstOrCreate(['name' => 'Admin']);
        $user->assignRole('Admin');

        $this->actingAs($user)->get('/portal/dashboard')->assertForbidden();
    }
}
