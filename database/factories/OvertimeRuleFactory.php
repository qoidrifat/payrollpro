<?php

namespace Database\Factories;

use App\Enums\OvertimeType;
use App\Models\OvertimeRule;
use Illuminate\Database\Eloquent\Factories\Factory;

class OvertimeRuleFactory extends Factory
{
    protected $model = OvertimeRule::class;

    public function definition(): array
    {
        return [
            'company_id'                => 1,
            'overtime_type'             => OvertimeType::Regular,
            'multiplier_first_hour'     => 1.5,
            'multiplier_subsequent_hours' => 1.5,
            'max_hours_per_day'         => 4,
            'max_hours_per_week'        => 14,
            'applicable_year'           => (int) date('Y'),
            'name'                      => 'Overtime Rule ' . OvertimeType::Regular->label(),
            'is_active'                 => true,
        ];
    }
}
