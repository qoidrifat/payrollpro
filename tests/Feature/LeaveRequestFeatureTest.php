<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
