<?php

namespace Tests\Feature;

use App\Events\EmployeeClockedOut;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class AttendanceFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_attendance_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/attendances');

        $response->assertStatus(200);
    }

    public function test_attendance_creation_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/attendances/create');

        $response->assertStatus(200);
    }

    public function test_attendance_can_be_created(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->actingAs($this->admin)->post('/attendances', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'status' => 'present',
            'type' => 'wfo',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'status' => 'present',
        ]);
    }

    public function test_attendance_can_be_viewed(): void
    {
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($this->admin)->get("/attendances/{$attendance->id}/edit");

        $response->assertStatus(200);
    }

    public function test_attendance_can_be_updated(): void
    {
        $attendance = Attendance::factory()->create([
            'status' => 'present',
        ]);

        $response = $this->actingAs($this->admin)->put("/attendances/{$attendance->id}", [
            'employee_id' => $attendance->employee_id,
            'date' => $attendance->date->format('Y-m-d'),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'status' => 'late',
            'type' => 'wfo',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'status' => 'late',
        ]);
    }

    public function test_attendance_can_be_deleted(): void
    {
        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/attendances/{$attendance->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    }

    public function test_today_status_api_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/api/today-attendance');

        $response->assertStatus(200);
    }

    public function test_my_qr_page_is_accessible(): void
    {
        $this->travelToOperationalTime();

        Employee::factory()->count(2)->create();

        $response = $this->actingAs($this->admin)->get('/my-qr');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/MyQr')
            ->where('mode', 'admin')
            ->has('employees', 2)
            ->has('employee.qr_in_url')
            ->has('employee.qr_out_url')
            ->has('qrExpiresAt')
        );
    }

    public function test_employee_my_qr_page_uses_own_employee_profile(): void
    {
        $this->travelToOperationalTime();

        $this->createEmployeeRoleWithAttendancePermission();
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $user->assignRole('Employee');

        $response = $this->actingAs($user)->get('/my-qr');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Attendance/MyQr')
            ->where('mode', 'employee')
            ->where('employee.id', $employee->id)
            ->has('employee.qr_in_url')
            ->has('employee.qr_out_url')
        );
    }

    public function test_employee_without_employee_record_cannot_see_other_attendances(): void
    {
        $this->createEmployeeRoleWithAttendancePermission();
        Attendance::factory()->count(3)->create();
        $user = User::factory()->create();
        $user->assignRole('Employee');

        $response = $this->actingAs($user)->get('/attendances');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Attendances/Index')
            ->where('total', 0)
        );
    }

    public function test_first_clock_out_returns_success_and_dispatches_event(): void
    {
        Event::fake([EmployeeClockedOut::class]);
        $this->travelToOperationalTime(16, 0);

        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $attendance = Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => null,
        ]);
        $token = $this->cacheAttendanceToken($employee);

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', 'attendance-test-agent')
            ->post("/scan/clock-out/{$employee->id}", [
                'attendance_token' => $token,
                'latitude' => -6.2,
                'longitude' => 106.8,
            ]);

        $response->assertSessionHas('success', 'Clock Out berhasil!');
        $this->assertNotNull($attendance->fresh()->clock_out);
        Event::assertDispatched(EmployeeClockedOut::class);
    }

    public function test_repeated_clock_out_returns_info(): void
    {
        Event::fake([EmployeeClockedOut::class]);
        $this->travelToOperationalTime(16, 0);

        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'clock_in' => '08:00:00',
            'clock_out' => '15:00:00',
        ]);
        $token = $this->cacheAttendanceToken($employee);

        $response = $this->actingAs($user)
            ->withHeader('User-Agent', 'attendance-test-agent')
            ->post("/scan/clock-out/{$employee->id}", [
                'attendance_token' => $token,
            ]);

        $response->assertSessionHas('info', 'Anda sudah Clock Out hari ini.');
        Event::assertNotDispatched(EmployeeClockedOut::class);
    }

    private function createEmployeeRoleWithAttendancePermission(): void
    {
        $permission = Permission::firstOrCreate(['name' => 'view-attendance']);
        $role = Role::firstOrCreate(['name' => 'Employee']);
        $role->givePermissionTo($permission);
    }

    private function cacheAttendanceToken(Employee $employee, string $action = 'out'): string
    {
        $token = 'valid-attendance-token';

        Cache::put("attendance_token:{$employee->id}:{$action}", [
            'token_hash' => hash('sha256', $token),
            'ip' => '127.0.0.1',
            'user_agent' => 'attendance-test-agent',
        ], now()->addMinutes(5));

        return $token;
    }

    private function travelToOperationalTime(int $hour = 10, int $minute = 0): void
    {
        $timezone = config('attendance.operational_hours.timezone', 'Asia/Jakarta');

        $this->travelTo(CarbonImmutable::parse("2026-06-10 {$hour}:{$minute}:00", $timezone));
    }
}
