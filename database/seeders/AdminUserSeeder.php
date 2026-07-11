<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // First, seed roles and permissions
        $this->call(RoleAndPermissionSeeder::class);
        $companyId = Company::query()->where('is_active', true)->value('id')
            ?? Company::query()->value('id');

        $admin = User::firstOrCreate(
            ['email' => 'admin@project-kp.test'],
            [
                'company_id' => $companyId,
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
                'account_status' => User::STATUS_ACTIVE,
                'approved_at' => now(),
            ]
        );
        $admin->forceFill([
            'company_id' => $admin->company_id ?? $companyId,
            'account_status' => User::STATUS_ACTIVE,
            'approved_at' => $admin->approved_at ?? now(),
            'suspended_at' => null,
        ])->save();
        $admin->assignRole('Admin');

        $hr = User::firstOrCreate(
            ['email' => 'hr@project-kp.test'],
            [
                'company_id' => $companyId,
                'name' => 'HR Manager',
                'password' => 'password',
                'email_verified_at' => now(),
                'account_status' => User::STATUS_ACTIVE,
                'approved_at' => now(),
            ]
        );
        $hr->forceFill([
            'company_id' => $hr->company_id ?? $companyId,
            'account_status' => User::STATUS_ACTIVE,
            'approved_at' => $hr->approved_at ?? now(),
            'suspended_at' => null,
        ])->save();
        $hr->assignRole('HR');
    }
}
