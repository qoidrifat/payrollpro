<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'date'        => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'clock_in'    => $this->faker->time('H:i:s'),
            'clock_out'   => $this->faker->time('H:i:s'),
            'status'      => 'present',
            'type'        => 'wfo',
            'latitude'    => $this->faker->latitude(-8, -6),
            'longitude'   => $this->faker->longitude(111, 114),
            'created_by'  => 'Test',
        ];
    }
}
