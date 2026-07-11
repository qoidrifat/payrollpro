<?php

namespace Tests\Feature;

use App\Enums\ManualAttendanceRequestStatus;
use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\ManualAttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ManualAttendanceRequestFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpRoles();
        $this->travelTo(now()->setTime(9, 0));
    }

    public function test_employee_can_create_pending_manual_clock_in_request_from_my_qr_flow(): void
    {
        [$user, $employee] = $this->createEmployeeUser();

        $response = $this->actingAs($user)->post('/manual-attendance-requests', [
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:15',
            'reason' => 'Kamera tidak bisa membaca QR kantor.',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('manual_attendance_requests', [
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'status' => ManualAttendanceRequestStatus::Pending->value,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_duplicate_pending_or_approved_request_for_same_date_and_type_is_rejected(): void
    {
        [$user, $employee] = $this->createEmployeeUser();
        ManualAttendanceRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:00',
            'reason' => 'QR tidak bisa dibuka dari perangkat employee.',
            'status' => ManualAttendanceRequestStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post('/manual-attendance-requests', [
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:10',
            'reason' => 'Koneksi tidak stabil saat scan QR.',
        ]);

        $response->assertSessionHasErrors('request_type');
    }

    public function test_employee_cannot_review_manual_attendance_request(): void
    {
        [$user, $employee] = $this->createEmployeeUser();
        $manualRequest = ManualAttendanceRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:00',
            'reason' => 'Kamera tidak bisa membuka scanner QR.',
            'status' => ManualAttendanceRequestStatus::Pending,
        ]);

        $response = $this->actingAs($user)->post("/manual-attendance-requests/{$manualRequest->id}/approve");

        $response->assertForbidden();
    }

    public function test_hr_cannot_review_their_own_manual_attendance_request(): void
    {
        $company = $this->company();
        $hrUser = User::factory()->create(['company_id' => $company->id]);
        $hrEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $hrUser->id,
        ]);
        $hrUser->assignRole('HR');

        $manualRequest = ManualAttendanceRequest::create([
            'company_id' => $hrEmployee->company_id,
            'employee_id' => $hrEmployee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:00',
            'reason' => 'Scanner QR error di perangkat HR sendiri.',
            'status' => ManualAttendanceRequestStatus::Pending,
        ]);

        $response = $this->actingAs($hrUser)->post("/manual-attendance-requests/{$manualRequest->id}/approve");

        $response->assertForbidden();
        $manualRequest->refresh();
        $this->assertSame(ManualAttendanceRequestStatus::Pending, $manualRequest->status);
    }

    public function test_admin_approval_creates_official_manual_attendance_record(): void
    {
        $admin = $this->createAdminUser();
        [, $employee] = $this->createEmployeeUser();
        $manualRequest = ManualAttendanceRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:05',
            'reason' => 'Scanner QR error di perangkat employee.',
            'status' => ManualAttendanceRequestStatus::Pending,
            'metadata' => [
                'ip' => '127.0.0.1',
                'timezone' => 'Asia/Jakarta',
                'user_agent' => 'feature-test',
            ],
        ]);

        $response = $this->actingAs($admin)->post("/manual-attendance-requests/{$manualRequest->id}/approve");

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');
        $manualRequest->refresh();
        $this->assertSame(ManualAttendanceRequestStatus::Approved, $manualRequest->status);
        $this->assertNotNull($manualRequest->attendance_id);
        $this->assertDatabaseHas('attendances', [
            'id' => $manualRequest->attendance_id,
            'employee_id' => $employee->id,
            'clock_in' => '08:05:00',
            'source' => 'manual',
            'approved_by' => $admin->id,
        ]);

        $auditLog = ActivityLog::where('subject_type', ManualAttendanceRequest::class)
            ->where('subject_id', $manualRequest->id)
            ->where('action', 'updated')
            ->latest()
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertContains('status', $auditLog->properties['changed']);
        $this->assertIsArray($auditLog->properties['before']['metadata']);
    }

    public function test_rejection_does_not_create_official_attendance_record(): void
    {
        $admin = $this->createAdminUser();
        [, $employee] = $this->createEmployeeUser();
        $manualRequest = ManualAttendanceRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:05',
            'reason' => 'Koneksi perangkat employee tidak stabil.',
            'status' => ManualAttendanceRequestStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->post("/manual-attendance-requests/{$manualRequest->id}/reject", [
            'rejection_reason' => 'Bukti tidak cukup jelas.',
        ]);

        $response->assertSessionHasNoErrors();
        $manualRequest->refresh();
        $this->assertSame(ManualAttendanceRequestStatus::Rejected, $manualRequest->status);
        $this->assertNull($manualRequest->attendance_id);
        $this->assertDatabaseMissing('attendances', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
        ]);
    }

    public function test_manual_clock_in_approval_is_rejected_when_official_clock_in_exists(): void
    {
        $admin = $this->createAdminUser();
        [, $employee] = $this->createEmployeeUser();

        \App\Models\Attendance::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '07:30:00',
            'status' => 'present',
            'type' => 'wfo',
        ]);

        $manualRequest = ManualAttendanceRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'request_type' => 'manual_clock_in',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:05',
            'reason' => 'Ajukan clock-in padahal sudah ada yang resmi.',
            'status' => ManualAttendanceRequestStatus::Pending,
        ]);

        $response = $this->actingAs($admin)->post("/manual-attendance-requests/{$manualRequest->id}/approve");

        $response->assertSessionHasErrors('request_type');
        $manualRequest->refresh();
        $this->assertSame(ManualAttendanceRequestStatus::Pending, $manualRequest->status);
        // Official clock-in stays intact, never silently overwritten.
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'clock_in' => '07:30:00',
        ]);
    }

    public function test_manual_clock_out_requires_existing_clock_in(): void
    {
        [$user] = $this->createEmployeeUser();

        $response = $this->actingAs($user)->post('/manual-attendance-requests', [
            'request_type' => 'manual_clock_out',
            'requested_date' => now()->toDateString(),
            'requested_time' => '08:55',
            'reason' => 'Koneksi terputus ketika melakukan clock out.',
        ]);

        $response->assertSessionHasErrors('request_type');
    }

    private function setUpRoles(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (['manage-attendance', 'view-attendance', 'view-dashboard'] as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'Admin'])->givePermissionTo(Permission::all());
        Role::firstOrCreate(['name' => 'HR'])->givePermissionTo(['manage-attendance', 'view-attendance', 'view-dashboard']);
        Role::firstOrCreate(['name' => 'Employee'])->givePermissionTo(['view-attendance', 'view-dashboard']);
    }

    private function createEmployeeUser(): array
    {
        $company = $this->company();
        $user = User::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
        ]);
        $user->assignRole('Employee');

        return [$user, $employee];
    }

    private function createAdminUser(): User
    {
        $user = User::factory()->create(['company_id' => $this->company()->id]);
        $user->assignRole('Admin');

        return $user;
    }

    private function company(): Company
    {
        return Company::firstOrCreate(
            ['slug' => 'test-company'],
            ['name' => 'Test Company', 'is_active' => true],
        );
    }
}
