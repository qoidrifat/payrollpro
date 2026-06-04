<?php

namespace Tests\Unit\Policy;

use App\Models\Attendance;
use App\Models\User;
use App\Policies\AttendancePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AttendancePolicyTest extends TestCase
{
    use RefreshDatabase;

    private AttendancePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new AttendancePolicy();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        Permission::create(['name' => 'manage-attendance']);
        Permission::create(['name' => 'view-attendance']);
        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());
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
    public function create_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');

        $this->assertTrue($this->policy->create($user));
    }

    #[Test]
    public function create_returns_false_for_user_without_permission(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($this->policy->create($user));
    }

    #[Test]
    public function update_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $attendance = Attendance::factory()->create();

        $this->assertTrue($this->policy->update($user, $attendance));
    }

    #[Test]
    public function delete_returns_true_for_admin(): void
    {
        $user = User::factory()->create()->assignRole('Admin');
        $attendance = Attendance::factory()->create();

        $this->assertTrue($this->policy->delete($user, $attendance));
    }

    #[Test]
    public function delete_returns_false_for_user_without_permission(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::factory()->create();

        $this->assertFalse($this->policy->delete($user, $attendance));
    }
}
