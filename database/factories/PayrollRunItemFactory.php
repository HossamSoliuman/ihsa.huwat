<?php

namespace Database\Factories;

use App\Models\PayrollRunEmployee;
use App\Models\PayrollRunItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PayrollRunItem> */
class PayrollRunItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payroll_run_employee_id' => PayrollRunEmployee::factory(),
            'item_type' => 'earning',
            'code' => 'basic',
            'label_ar' => 'الراتب الأساسي',
            'amount' => 7000,
            'calculation_details' => ['formula_ar' => 'مبلغ ثابت: 7,000.00'],
        ];
    }
}
