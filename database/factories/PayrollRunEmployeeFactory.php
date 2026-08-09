<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRunEmployee> */
class PayrollRunEmployeeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'employee_number' => fake()->numerify('HWT-#####'),
            'employee_name' => fake()->name(),
            'department_name' => fake()->word(),
            'job_title_name' => fake()->jobTitle(),
            'port_name' => null,
            'contract_type' => 'permanent',
            'basic_salary' => 7000,
            'total_earnings' => 7000,
            'total_deductions' => 0,
            'net_salary' => 7000,
            'status' => 'ok',
        ];
    }
}
