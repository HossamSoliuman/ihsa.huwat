<?php

namespace Database\Factories;

use App\Models\SalaryComponent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SalaryComponent> */
class SalaryComponentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('salary_????####'),
            'name_ar' => 'مكوّن راتب '.fake()->unique()->numerify('###'),
            'component_type' => SalaryComponent::TYPE_EARNING,
            'calculation_type' => SalaryComponent::CALCULATION_FIXED,
            'is_basic' => false,
            'sort_order' => fake()->numberBetween(1, 9) * 10,
            'is_active' => true,
        ];
    }

    public function percentage(): static
    {
        return $this->state(fn (): array => [
            'calculation_type' => SalaryComponent::CALCULATION_PERCENT_OF_BASIC,
        ]);
    }

    public function deduction(): static
    {
        return $this->state(fn (): array => [
            'component_type' => SalaryComponent::TYPE_DEDUCTION,
        ]);
    }
}
