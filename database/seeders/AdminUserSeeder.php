<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleAndPermissionSeeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // First, seed roles and permissions
        $this->call(RoleAndPermissionSeeder::class);

        $admin = User::firstOrCreate(
            ['email' => 'admin@project-kp.test'],
            [
                'name' => 'Admin',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('Admin');

        $hr = User::firstOrCreate(
            ['email' => 'hr@project-kp.test'],
            [
                'name' => 'HR Manager',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );
        $hr->assignRole('HR');
    }
}
