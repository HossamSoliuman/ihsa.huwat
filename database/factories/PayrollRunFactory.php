<?php

namespace Database\Factories;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRun> */
class PayrollRunFactory extends Factory
{
    public function definition(): array
    {
        $period = today()->startOfMonth();

        return [
            'run_number' => 'PR-'.$period->format('Y-m'),
            'period_year' => $period->year,
            'period_month' => $period->month,
            'period_start' => $period,
            'period_end' => $period->endOfMonth(),
            'status' => PayrollRun::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }

    public function calculated(): static
    {
        return $this->state(fn (): array => [
            'status' => PayrollRun::STATUS_CALCULATED,
            'calculated_at' => now(),
        ]);
    }
}
