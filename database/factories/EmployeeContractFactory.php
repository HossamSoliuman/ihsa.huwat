<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
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
            'contract_number' => fake()->unique()->numerify('HWT-C-#####'),
            'contract_type' => 'permanent',
            'start_date' => today()->subYear(),
            'end_date' => null,
            'probation_end_date' => today()->subYear()->addMonths(3),
            'working_hours_per_day' => 8,
            'working_days_per_week' => 6,
            'status' => 'active',
            'note' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => 'draft']);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => 'expired',
            'end_date' => today()->subDay(),
        ]);
    }

    public function temporary(): static
    {
        return $this->state(fn (): array => [
            'contract_type' => 'temporary',
            'end_date' => today()->addYear(),
        ]);
    }
}
