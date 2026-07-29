<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Payroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_month' => today()->month,
            'period_year' => today()->year,
            'base_salary' => 7000,
            'allowances' => 0,
            'overtime_hours' => 0,
            'overtime_amount' => 0,
            'bonuses' => 0,
            'deductions' => 0,
            'net_salary' => 7000,
            'paid_status' => 'pending',
        ];
    }
}
