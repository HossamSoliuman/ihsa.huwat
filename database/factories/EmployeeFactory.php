<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Port;
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
            'employee_number' => fake()->unique()->numerify('HWT-#####'),
            'national_id' => fake()->unique()->numerify('1#########'),
            'nationality' => 'saudi',
            'date_of_birth' => fake()->dateTimeBetween('-55 years', '-20 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'phone' => fake()->numerify('05########'),
            'email' => fake()->unique()->safeEmail(),
            'department_id' => Department::factory(),
            'job_title_id' => JobTitle::factory(),
            'port_id' => Port::factory(),
            'hire_date' => today()->subYear(),
            'status' => 'active',
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (): array => ['status' => 'draft']);
    }

    public function terminated(): static
    {
        return $this->state(fn (): array => [
            'status' => 'terminated',
            'termination_date' => today(),
            'termination_reason' => fake()->sentence(),
        ]);
    }
}
