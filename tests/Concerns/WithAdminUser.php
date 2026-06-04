<?php

namespace Tests\Concerns;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

trait WithAdminUser
{
    protected User $admin;

    protected function setUpAdmin(): void
    {
        // Clear Spatie cache so permissions are recognized immediately
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Seed permissions
        $permissions = [
            'manage-employees',
            'manage-attendance',
            'manage-leaves',
            'view-attendance',
            'manage-payroll',
            'view-payroll',
            'manage-settings',
            'view-reports',
            'view-dashboard',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $role = Role::firstOrCreate(['name' => 'Admin']);
        $role->givePermissionTo(Permission::all());

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }
}
