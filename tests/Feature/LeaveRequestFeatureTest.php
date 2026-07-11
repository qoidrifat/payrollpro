<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class LeaveRequestFeatureTest extends TestCase
{
    use RefreshDatabase;
    use WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
    }

    public function test_admin_can_view_leave_requests_page(): void
    {
        $this->makeLeaveRequest();

        $response = $this->actingAs($this->admin)->get('/leave-requests');

        $response->assertStatus(200);
    }

    public function test_admin_can_approve_pending_leave_request(): void
    {
        $leaveRequest = $this->makeLeaveRequest();

        $response = $this->actingAs($this->admin)
            ->post(route('leave-requests.approve', $leaveRequest));

        $response->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'rejection_reason' => null,
        ]);
    }

    public function test_admin_can_reject_pending_leave_request_with_reason(): void
    {
        $leaveRequest = $this->makeLeaveRequest();

        $response = $this->actingAs($this->admin)
            ->post(route('leave-requests.reject', $leaveRequest), [
                'rejection_reason' => 'Jadwal operasional tim belum memungkinkan.',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'rejected',
            'approved_by' => $this->admin->id,
            'rejection_reason' => 'Jadwal operasional tim belum memungkinkan.',
        ]);
    }

    public function test_reject_requires_reason(): void
    {
        $leaveRequest = $this->makeLeaveRequest();

        $response = $this->actingAs($this->admin)
            ->post(route('leave-requests.reject', $leaveRequest), [
                'rejection_reason' => '',
            ]);

        $response->assertSessionHasErrors('rejection_reason');

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
        ]);
    }

    public function test_processed_leave_request_cannot_be_approved_again(): void
    {
        $leaveRequest = $this->makeLeaveRequest([
            'status' => 'approved',
            'approved_by' => $this->admin->id,
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('leave-requests.approve', $leaveRequest));

        $response->assertRedirect();

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'approved',
            'approved_by' => $this->admin->id,
        ]);
    }

    public function test_hr_cannot_review_their_own_leave_request(): void
    {
        Role::firstOrCreate(['name' => 'HR']);

        $employee = Employee::factory()->create();
        $hr = User::factory()->create(['company_id' => $employee->company_id]);
        $employee->forceFill(['user_id' => $hr->id])->save();
        $hr->assignRole('HR');

        $leaveRequest = LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(2)->toDateString(),
            'total_days' => 3,
            'reason' => 'Cuti pribadi HR.',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($hr)->post(route('leave-requests.approve', $leaveRequest));

        $response->assertForbidden();
        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'pending',
        ]);
    }

    private function makeLeaveRequest(array $overrides = []): LeaveRequest
    {
        $employee = Employee::factory()->create();

        return LeaveRequest::create(array_merge([
            'employee_id' => $employee->id,
            'leave_type' => 'annual',
            'start_date' => now()->addWeek()->toDateString(),
            'end_date' => now()->addWeek()->addDays(2)->toDateString(),
            'total_days' => 3,
            'reason' => 'Keperluan keluarga.',
            'status' => 'pending',
        ], $overrides));
    }
}
