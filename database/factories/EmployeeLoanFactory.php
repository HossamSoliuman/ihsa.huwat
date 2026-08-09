<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeLoan> */
class EmployeeLoanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'loan_number' => fake()->unique()->numerify('LN-#####'),
            'amount' => 12000,
            'instalments_count' => 12,
            'instalment_amount' => 1000,
            'first_instalment_month' => today()->startOfMonth(),
            'reason' => fake()->sentence(),
            'status' => 'requested',
        ];
    }
}
