<?php

namespace Database\Factories;

use App\Models\EmployeeLoan;
use App\Models\LoanInstalment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoanInstalment> */
class LoanInstalmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'loan_id' => EmployeeLoan::factory(),
            'instalment_number' => 1,
            'due_year' => today()->year,
            'due_month' => today()->month,
            'amount' => 1000,
            'paid_amount' => 0,
            'status' => 'scheduled',
        ];
    }
}
