<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EmployeeUserSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        if ($employees->isEmpty()) {
            $this->command->warn('No employees found. Run DummyDataSeeder first.');
            return;
        }

        foreach ($employees as $employee) {
            $email = Str::lower(
                $employee->first_name . '.' . ($employee->last_name ?? 'employee')
                . '.' . $employee->id . '@project-kp.test'
            );
            $email = str_replace(' ', '_', $email);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $employee->first_name . ' ' . ($employee->last_name ?? ''),
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            // Link user to employee
            if (!$employee->user_id) {
                $employee->update(['user_id' => $user->id]);
            }

            // Assign role: HR untuk Maya Anggraini (Finance & HR), Employee untuk sisanya
            if ($employee->first_name === 'Maya' && $employee->last_name === 'Anggraini') {
                $user->syncRoles('HR');
            } else {
                $user->assignRole('Employee');
            }
        }

        $this->command->info(count($employees) . ' employee user accounts created.');
    }
}
