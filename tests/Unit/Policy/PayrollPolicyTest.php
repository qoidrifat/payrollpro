<?php

namespace Tests\Unit\Policy;

use App\Enums\PayrollStatus;
use App\Models\Payroll;
use App\Models\User;
use App\Policies\PayrollPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PayrollPolicyTest extends TestCase
{
    use RefreshDatabase;

    private PayrollPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PayrollPolicy();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'manage-payroll']);
        Permission::create(['name' => 'view-payroll']);
        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo(Permission::all());
    }

    #[Test]
    public function viewAny_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($this->policy->viewAny($user));
    }

    #[Test]
    public function viewAny_returns_false_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function update_returns_true_for_draft_payroll(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Draft]);

        $this->assertTrue($this->policy->update($user, $payroll));
    }

    #[Test]
    public function update_returns_false_for_processed_payroll(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Processed]);

        $this->assertFalse($this->policy->update($user, $payroll));
    }

    #[Test]
    public function delete_returns_true_for_draft_payroll(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Draft]);

        $this->assertTrue($this->policy->delete($user, $payroll));
    }

    #[Test]
    public function delete_returns_false_for_approved_payroll(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $payroll = Payroll::factory()->create(['status' => PayrollStatus::Approved]);

        $this->assertFalse($this->policy->delete($user, $payroll));
    }

    #[Test]
    public function update_uses_enum_comparison_not_string(): void
    {
        // Verify that passing string 'draft' also works via enum casting
        $user = User::factory()->create()->assignRole('Admin');
        $payroll = Payroll::factory()->create(['status' => 'draft']);

        $this->assertTrue($this->policy->update($user, $payroll));
    }

    #[Test]
    public function delete_returns_false_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $payroll = Payroll::factory()->create();

        $this->assertFalse($this->policy->delete($user, $payroll));
    }
}
