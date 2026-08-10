<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollAdjustment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollAdjustment> */
class PayrollAdjustmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'salary_component_id' => null,
            'adjustment_type' => 'earning',
            'period_year' => today()->year,
            'period_month' => today()->month,
            'amount' => 500,
            'reason' => fake()->sentence(),
            'status' => PayrollAdjustment::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollAdjustment::STATUS_APPROVED,
            'approved_by' => User::factory(),
        ]);
    }

    public function deduction(): static
    {
        return $this->state(fn (): array => ['adjustment_type' => 'deduction']);
    }
}
