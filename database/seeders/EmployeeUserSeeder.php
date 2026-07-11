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
                $employee->first_name.'.'.($employee->last_name ?? 'employee')
                .'.'.$employee->id.'@project-kp.test'
            );
            $email = str_replace(' ', '_', $email);

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'company_id' => $employee->company_id,
                    'name' => $employee->first_name.' '.($employee->last_name ?? ''),
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'account_status' => User::STATUS_ACTIVE,
                    'approved_at' => now(),
                ]
            );

            $user->forceFill([
                'company_id' => $employee->company_id,
                'account_status' => User::STATUS_ACTIVE,
                'approved_at' => $user->approved_at ?? now(),
                'suspended_at' => null,
            ])->save();

            // Link user to employee
            if (! $employee->user_id) {
                $employee->forceFill(['user_id' => $user->id])->save();
            }

            // Assign role: HR untuk Maya Anggraini (Finance & HR), Employee untuk sisanya
            if ($employee->first_name === 'Maya' && $employee->last_name === 'Anggraini') {
                $user->syncRoles('HR');
            } else {
                $user->syncRoles('Employee');
            }
        }

        $this->command->info(count($employees).' employee user accounts created.');
    }
}
