<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

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

        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->givePermissionTo(Permission::all());

        $hr = Role::firstOrCreate(['name' => 'HR']);
        $hr->givePermissionTo([
            'manage-employees',
            'manage-attendance',
            'manage-leaves',
            'view-attendance',
            'manage-payroll',
            'view-payroll',
            'view-reports',
            'view-dashboard',
        ]);

        $employee = Role::firstOrCreate(['name' => 'Employee']);
        $employee->givePermissionTo([
            'view-attendance',
            'view-payroll',
            'view-dashboard',
        ]);
    }
}
