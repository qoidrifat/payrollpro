<?php

namespace Database\Factories;

use App\Models\BpjsConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class BpjsConfigFactory extends Factory
{
    protected $model = BpjsConfig::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'type' => 'kesehatan',
            'payer' => 'company',
            'rate_percentage' => 4.0,
            'salary_cap' => null,
            'applicable_year' => (int) date('Y'),
            'is_active' => true,
        ];
    }
}
