<?php

namespace Database\Factories;

use App\Models\Pph21Config;
use Illuminate\Database\Eloquent\Factories\Factory;

class Pph21ConfigFactory extends Factory
{
    protected $model = Pph21Config::class;

    public function definition(): array
    {
        return [
            'income_bracket_start' => 0,
            'income_bracket_end' => 60000000,
            'rate_percentage' => 5,
            'applicable_year' => (int) date('Y'),
            'is_active' => true,
        ];
    }
}
