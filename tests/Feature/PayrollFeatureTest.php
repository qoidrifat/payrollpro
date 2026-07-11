<?php

namespace Tests\Feature;

use App\Enums\ApprovalStatus;
use App\Enums\PayrollStatus;
use App\Jobs\ProcessPayroll as ProcessPayrollJob;
use App\Models\Approval;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Notifications\PayrollProcessedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\WithAdminUser;
use Tests\TestCase;

class PayrollFeatureTest extends TestCase
{
    use RefreshDatabase, WithAdminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdmin();
        $this->ensureHrRoleExists();
    }

    public function test_payroll_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/payroll');

        $response->assertStatus(200);
    }

    public function test_payroll_creation_page_is_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get('/payroll/create');

        $response->assertStatus(200);
    }

    public function test_payroll_can_be_created(): void
    {
        $response = $this->actingAs($this->admin)->post('/payroll', [
            'name' => 'Payroll Januari 2026',
            'period_start' => '2026-01-01',
            'period_end' => '2026-01-31',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payrolls', [
            'name' => 'Payroll Januari 2026',
            'status' => 'draft',
        ]);
    }

    public function test_payroll_can_be_viewed(): void
    {
        $payroll = Payroll::factory()->create();

        $response = $this->actingAs($this->admin)->get("/payroll/{$payroll->id}");

        $response->assertStatus(200);
    }

    public function test_payroll_can_be_updated(): void
    {
        $payroll = Payroll::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->admin)->put("/payroll/{$payroll->id}", [
            'name' => 'Updated Payroll',
            'period_start' => $payroll->period_start->format('Y-m-d'),
            'period_end' => $payroll->period_end->format('Y-m-d'),
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'name' => 'Updated Payroll',
        ]);
    }

    public function test_payroll_can_be_deleted(): void
    {
        $payroll = Payroll::factory()->create();

        $response = $this->actingAs($this->admin)->delete("/payroll/{$payroll->id}");

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('payrolls', ['id' => $payroll->id]);
    }

    public function test_payroll_process_requires_active_employees(): void
    {
        $payroll = Payroll::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->admin)->post("/payroll/{$payroll->id}/process");

        // No active employees, should fail gracefully
        $response->assertSessionHasNoErrors();
    }

    public function test_payroll_process_sets_processing_state_and_preserves_processor(): void
    {
        Queue::fake();
        Employee::factory()->create(['is_active' => true]);
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Draft]);

        $response = $this->actingAs($this->admin)->post("/payroll/{$payroll->id}/process");

        $response->assertRedirect(route('payroll.show', $payroll));
        $this->assertDatabaseHas('payrolls', [
            'id' => $payroll->id,
            'status' => PayrollStatus::Processing->value,
            'processed_by' => $this->admin->id,
            'total_employees' => 1,
        ]);
        Queue::assertPushed(ProcessPayrollJob::class);
    }

    public function test_payroll_process_blocked_when_overlapping_period_active(): void
    {
        Queue::fake();
        Employee::factory()->create(['is_active' => true]);

        // An active payroll already covers April 2026.
        Payroll::factory()->create([
            'status' => PayrollStatus::Processed,
            'period_start' => '2026-04-01',
            'period_end' => '2026-04-30',
        ]);

        // A draft payroll for an overlapping window must be rejected.
        $draft = Payroll::factory()->create([
            'status' => PayrollStatus::Draft,
            'period_start' => '2026-04-15',
            'period_end' => '2026-05-14',
        ]);

        $response = $this->actingAs($this->admin)->post("/payroll/{$draft->id}/process");

        $response->assertRedirect(route('payroll.show', $draft));
        // Draft stays draft; no job dispatched for the second run.
        $this->assertDatabaseHas('payrolls', [
            'id' => $draft->id,
            'status' => PayrollStatus::Draft->value,
        ]);
        Queue::assertNotPushed(ProcessPayrollJob::class);
    }

    public function test_payroll_index_can_be_filtered(): void
    {
        Payroll::factory()->create(['status' => 'draft']);
        Payroll::factory()->create(['status' => 'processed']);

        $response = $this->actingAs($this->admin)->get('/payroll?status=draft');

        $response->assertStatus(200);
    }

    public function test_admin_can_approve_processed_payroll(): void
    {
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Processed]);

        $response = $this->actingAs($this->admin)->post("/payroll/{$payroll->id}/approve");

        $response->assertRedirect(route('payroll.show', $payroll));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('approvals', [
            'approvable_type' => Payroll::class,
            'approvable_id' => $payroll->id,
            'status' => ApprovalStatus::Approved->value,
            'approver_id' => $this->admin->id,
        ]);
    }

    public function test_hr_can_approve_processed_payroll(): void
    {
        $hr = $this->createHrUser();
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Processed]);

        $response = $this->actingAs($hr)->post("/payroll/{$payroll->id}/approve");

        $response->assertRedirect(route('payroll.show', $payroll));
        $response->assertSessionHas('success');
        $this->assertSame(1, Approval::where('approvable_type', Payroll::class)
            ->where('approvable_id', $payroll->id)
            ->where('status', ApprovalStatus::Approved->value)
            ->count());
    }

    public function test_draft_payroll_cannot_be_approved(): void
    {
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Draft]);

        $response = $this->actingAs($this->admin)->post("/payroll/{$payroll->id}/approve");

        $response->assertForbidden();
    }

    public function test_generate_payslips_error_redirects_to_existing_payroll_route(): void
    {
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Approved]);

        $response = $this->actingAs($this->admin)->post("/payroll/{$payroll->id}/generate-payslips");

        $response->assertRedirect(route('payroll.show', $payroll));
        $response->assertSessionHas('error', 'Tidak ada item penggajian untuk membuat slip gaji.');
    }

    public function test_payroll_processed_notification_uses_existing_payroll_route(): void
    {
        $payroll = Payroll::factory()->create([
            'status' => PayrollStatus::Processed,
            'period_start' => '2026-06-01',
            'period_end' => '2026-06-30',
        ]);

        $mail = (new PayrollProcessedNotification($payroll))->toMail($this->admin);

        $this->assertSame(route('payroll.show', $payroll), $mail->actionUrl);
    }

    private function ensureHrRoleExists(): void
    {
        $permissions = Permission::whereIn('name', [
            'manage-employees',
            'manage-attendance',
            'manage-leaves',
            'view-attendance',
            'manage-payroll',
            'view-payroll',
            'view-reports',
            'view-dashboard',
        ])->get();

        $role = Role::firstOrCreate(['name' => 'HR']);
        $role->givePermissionTo($permissions);
    }

    private function createHrUser(): User
    {
        $this->ensureHrRoleExists();

        $user = User::factory()->create();
        $user->assignRole('HR');

        return $user;
    }
}
