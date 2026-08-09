<?php

namespace Database\Factories;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LeaveType> */
class LeaveTypeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('leave_????####'),
            'name_ar' => 'إجازة '.fake()->unique()->numerify('###'),
            'is_paid' => true,
            'annual_days' => fake()->randomElement([5, 10, 21, 30]),
            'payroll_effect' => LeaveType::PAYROLL_NONE,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 9) * 10,
        ];
    }

    public function unpaid(): static
    {
        return $this->state(fn (): array => [
            'is_paid' => false,
            'annual_days' => null,
            'payroll_effect' => LeaveType::PAYROLL_UNPAID_DEDUCTION,
        ]);
    }
}
