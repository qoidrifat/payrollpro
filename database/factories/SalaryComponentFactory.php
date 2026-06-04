<?php

namespace Database\Factories;

use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryComponentFactory extends Factory
{
    protected $model = SalaryComponent::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement([
                'Tunjangan Makan', 'Tunjangan Transport', 'Tunjangan Kesehatan',
                'Bonus Kinerja', 'THR', 'Lembur',
                'Pinjaman Koperasi', 'Potongan Keterlambatan',
            ]),
            'type' => $this->faker->randomElement(['allowance', 'deduction', 'bonus', 'overtime']),
            'amount' => $this->faker->numberBetween(100000, 2000000),
            'is_taxable' => $this->faker->boolean(70),
            'is_active' => true,
            'effective_from' => now()->startOfYear(),
        ];
    }
}
