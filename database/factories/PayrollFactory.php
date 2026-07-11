<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::query()->where('is_active', true)->value('id') ?? Company::query()->value('id'),
            'name' => 'Payroll '.$this->faker->monthName().' '.$this->faker->year(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'status' => 'draft',
            'total_gross' => 0,
            'total_deductions' => 0,
            'total_net' => 0,
            'total_employees' => 0,
        ];
    }
}
