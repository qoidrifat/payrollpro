<?php

namespace Tests\Unit\Policy;

use App\Models\Employee;
use App\Models\User;
use App\Policies\EmployeePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeePolicyTest extends TestCase
{
    use RefreshDatabase;

    private EmployeePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EmployeePolicy();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'manage-employees']);
        $role = Role::create(['name' => 'Admin']);
        $role->givePermissionTo('manage-employees');
        Role::create(['name' => 'HR']);
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
    public function viewAny_returns_false_for_hr(): void
    {
        $user = User::factory()->create()->assignRole('HR');

        $this->assertFalse($this->policy->viewAny($user));
    }

    #[Test]
    public function create_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function view_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $employee = Employee::factory()->create();

        $this->assertTrue($this->policy->view($user, $employee));
    }

    #[Test]
    public function update_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $employee = Employee::factory()->create();

        $this->assertTrue($this->policy->update($user, $employee));
    }

    #[Test]
    public function delete_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $employee = Employee::factory()->create();

        $this->assertTrue($this->policy->delete($user, $employee));
    }

    #[Test]
    public function delete_returns_false_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create();

        $this->assertFalse($this->policy->delete($user, $employee));
    }
}
