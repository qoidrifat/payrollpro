<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::query()->where('is_active', true)->value('id') ?? Company::query()->value('id'),
            'nik' => $this->faker->numerify('################'),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'position' => $this->faker->jobTitle(),
            'department' => $this->faker->randomElement(['IT', 'Finance', 'HR', 'Operations']),
            'join_date' => $this->faker->dateTimeBetween('-6 years', '-1 year')->format('Y-m-d'),
            'employment_status' => $this->faker->randomElement(['permanent', 'contract', 'probation', 'intern']),
            'base_salary' => $this->faker->numberBetween(3000000, 15000000),
            'marital_status' => $this->faker->randomElement(['single', 'married']),
            'dependents_count' => $this->faker->numberBetween(0, 5),
            'is_active' => true,
        ];
    }
}
