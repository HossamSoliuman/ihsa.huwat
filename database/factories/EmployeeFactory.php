<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_number' => fake()->unique()->bothify('EMP-#####'),
            'job_title' => fake()->jobTitle(),
            'hire_date' => today()->subYear(),
            'contract_type' => 'permanent',
            'base_salary' => 7000,
            'status' => 'active',
        ];
    }
}
