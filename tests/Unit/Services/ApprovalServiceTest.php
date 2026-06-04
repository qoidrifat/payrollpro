<?php

namespace Tests\Unit\Services;

use App\Enums\ApprovalLevel;
use App\Enums\ApprovalStatus;
use App\Models\Approval;
use App\Models\Payroll;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // ApprovalService::notifyLevelUsers() uses User::role() which
        // requires roles to exist in the database
        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'HR']);

        $this->service = new ApprovalService();
    }

    public function test_initialize_chain_creates_three_levels(): void
    {
        $payroll = Payroll::factory()->create();

        $this->service->initializeChain($payroll);

        $approvals = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->get();

        $this->assertCount(3, $approvals);
        $this->assertEquals(ApprovalLevel::Manager->value, $approvals[0]->level->value);
        $this->assertEquals(ApprovalLevel::HR->value, $approvals[1]->level->value);
        $this->assertEquals(ApprovalLevel::Finance->value, $approvals[2]->level->value);

        foreach ($approvals as $approval) {
            $this->assertEquals(ApprovalStatus::Pending, $approval->status);
        }
    }

    public function test_approve_pending_approval(): void
    {
        $payroll = Payroll::factory()->create();
        $approver = User::factory()->create();

        $this->service->initializeChain($payroll);

        $pendingApproval = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->where('level', ApprovalLevel::Manager->value)
            ->first();

        $this->service->approve($pendingApproval, $approver, 'Looks good');

        $pendingApproval->refresh();

        $this->assertEquals(ApprovalStatus::Approved, $pendingApproval->status);
        $this->assertEquals($approver->id, $pendingApproval->approver_id);
        $this->assertNotNull($pendingApproval->approved_at);
        $this->assertEquals('Looks good', $pendingApproval->comments);
    }

    public function test_approve_non_pending_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);

        $payroll = Payroll::factory()->create();
        $approver = User::factory()->create();

        $this->service->initializeChain($payroll);

        $approval = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->first();

        // Approve twice
        $this->service->approve($approval, $approver);
        $this->service->approve($approval, $approver);
    }

    public function test_reject_pending_approval(): void
    {
        $payroll = Payroll::factory()->create();
        $approver = User::factory()->create();

        $this->service->initializeChain($payroll);

        $pendingApproval = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->first();

        $this->service->reject($pendingApproval, $approver, 'Not approved');

        $pendingApproval->refresh();

        $this->assertEquals(ApprovalStatus::Rejected, $pendingApproval->status);
        $this->assertEquals($approver->id, $pendingApproval->approver_id);
        $this->assertNotNull($pendingApproval->rejected_at);
    }

    public function test_reject_without_comments_throws_exception(): void
    {
        $this->expectException(\TypeError::class);

        $payroll = Payroll::factory()->create();
        $approver = User::factory()->create();

        $this->service->initializeChain($payroll);

        $approval = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->first();

        // comments is required (not nullable in reject method signature)
        $this->service->reject($approval, $approver, null);
    }

    public function test_cancel_chain(): void
    {
        $payroll = Payroll::factory()->create();

        $this->service->initializeChain($payroll);
        $this->service->cancelChain($payroll);

        $pendingCount = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->where('status', ApprovalStatus::Pending)
            ->count();

        $this->assertEquals(0, $pendingCount);

        $cancelledCount = Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->where('status', ApprovalStatus::Cancelled)
            ->count();

        $this->assertEquals(3, $cancelledCount);
    }
}
