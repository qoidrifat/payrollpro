<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccountManagementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_account_management_page(): void
    {
        $admin = $this->adminUser();
        $employee = User::factory()->create([
            'account_status' => User::STATUS_PENDING,
        ]);
        $employee->assignRole('Employee');

        $response = $this->actingAs($admin)->get('/admin/accounts');

        $response->assertStatus(200);
    }

    public function test_admin_can_activate_pending_account(): void
    {
        $admin = $this->adminUser();
        $employee = User::factory()->create([
            'account_status' => User::STATUS_PENDING,
        ]);
        $employee->assignRole('Employee');

        $response = $this->actingAs($admin)->post("/admin/accounts/{$employee->id}/activate");

        $response->assertRedirect();
        $employee->refresh();

        $this->assertSame(User::STATUS_ACTIVE, $employee->account_status);
        $this->assertNotNull($employee->approved_at);
        $this->assertSame($admin->id, $employee->approved_by);
    }

    public function test_admin_can_change_account_role_to_hr(): void
    {
        $admin = $this->adminUser();
        $employee = User::factory()->create([
            'account_status' => User::STATUS_ACTIVE,
        ]);
        $employee->assignRole('Employee');

        $response = $this->actingAs($admin)->put("/admin/accounts/{$employee->id}/role", [
            'role' => 'HR',
        ]);

        $response->assertRedirect();

        $this->assertTrue($employee->fresh()->hasRole('HR'));
        $this->assertFalse($employee->fresh()->hasRole('Employee'));
    }

    public function test_admin_cannot_manage_account_from_another_company_context(): void
    {
        $companyA = Company::create(['name' => 'Company A', 'slug' => 'company-a', 'is_active' => true]);
        $companyB = Company::create(['name' => 'Company B', 'slug' => 'company-b', 'is_active' => true]);
        app()->instance('current_company_id', $companyA->id);

        $admin = $this->adminUser(['company_id' => $companyA->id]);
        $otherAccount = User::factory()->create([
            'company_id' => $companyB->id,
            'account_status' => User::STATUS_PENDING,
        ]);
        $otherAccount->assignRole('Employee');

        $response = $this->actingAs($admin)->post("/admin/accounts/{$otherAccount->id}/activate");

        $response->assertForbidden();
        $this->assertSame(User::STATUS_PENDING, $otherAccount->fresh()->account_status);
    }

    private function adminUser(array $attributes = []): User
    {
        Role::firstOrCreate(['name' => 'Admin']);
        Role::firstOrCreate(['name' => 'Employee']);
        Role::firstOrCreate(['name' => 'HR']);

        $admin = User::factory()->create(array_merge([
            'account_status' => User::STATUS_ACTIVE,
            'approved_at' => now(),
        ], $attributes));
        $admin->assignRole('Admin');

        return $admin;
    }
}
