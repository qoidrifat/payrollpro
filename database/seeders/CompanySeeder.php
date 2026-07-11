<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['slug' => 'project-kp'],
            [
                'name' => 'Project KP',
                'is_active' => true,
                'subscription_plan' => 'internal',
            ]
        );
    }
}
