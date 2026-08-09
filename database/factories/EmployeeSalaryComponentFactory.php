<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeSalaryComponent> */
class EmployeeSalaryComponentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'salary_component_id' => SalaryComponent::factory(),
            'amount' => 7000,
            'percentage' => null,
            'effective_from' => today()->startOfMonth(),
            'effective_to' => null,
            'created_by' => User::factory(),
        ];
    }
}
