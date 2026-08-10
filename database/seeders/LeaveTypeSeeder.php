<?php

namespace Database\Seeders;

use App\Models\LeaveType;
use Illuminate\Database\Seeder;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'annual', 'name_ar' => 'إجازة سنوية', 'is_paid' => true, 'annual_days' => 21, 'payroll_effect' => LeaveType::PAYROLL_NONE, 'sort_order' => 10],
            ['code' => 'sick', 'name_ar' => 'إجازة مرضية', 'is_paid' => true, 'annual_days' => 30, 'payroll_effect' => LeaveType::PAYROLL_NONE, 'sort_order' => 20],
            ['code' => 'unpaid', 'name_ar' => 'إجازة بدون راتب', 'is_paid' => false, 'annual_days' => null, 'payroll_effect' => LeaveType::PAYROLL_UNPAID_DEDUCTION, 'sort_order' => 30],
            ['code' => 'emergency', 'name_ar' => 'إجازة اضطرارية', 'is_paid' => true, 'annual_days' => 5, 'payroll_effect' => LeaveType::PAYROLL_NONE, 'sort_order' => 40],
        ] as $leaveType) {
            LeaveType::query()->firstOrCreate(['code' => $leaveType['code']], $leaveType);
        }
    }
}
