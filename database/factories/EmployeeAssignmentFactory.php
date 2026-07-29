<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Port;
use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeAssignment>
 */
class EmployeeAssignmentFactory extends Factory
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
            'port_id' => Port::factory(),
            'shift_id' => fn () => Shift::query()->value('id') ?? Shift::factory(),
            'assignment_date' => today(),
            'is_temporary' => false,
        ];
    }
}
