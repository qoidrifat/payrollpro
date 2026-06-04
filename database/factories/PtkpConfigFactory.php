<?php

namespace Database\Factories;

use App\Models\PtkpConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

class PtkpConfigFactory extends Factory
{
    protected $model = PtkpConfig::class;

    public function definition(): array
    {
        return [
            'category' => 'TK/0',
            'annual_amount' => 54000000,
            'applicable_year' => (int) date('Y'),
            'is_active' => true,
        ];
    }
}
