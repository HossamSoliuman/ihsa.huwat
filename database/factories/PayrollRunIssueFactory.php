<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollRunIssue;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRunIssue> */
class PayrollRunIssueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_run_id' => PayrollRun::factory(),
            'employee_id' => Employee::factory(),
            'level' => 'warning',
            'code' => 'missing_iban',
            'message_ar' => 'لا يوجد رقم آيبان مسجل للموظف.',
            'resolved' => false,
        ];
    }
}
