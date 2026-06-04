<?php

namespace Database\Factories;

use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayrollFactory extends Factory
{
    protected $model = Payroll::class;

    public function definition(): array
    {
        return [
            'name' => 'Payroll ' . $this->faker->monthName() . ' ' . $this->faker->year(),
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
