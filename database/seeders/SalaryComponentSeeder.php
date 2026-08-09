<?php

namespace Database\Seeders;

use App\Models\SalaryComponent;
use Illuminate\Database\Seeder;

class SalaryComponentSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'basic', 'name_ar' => 'الراتب الأساسي', 'component_type' => SalaryComponent::TYPE_EARNING, 'calculation_type' => SalaryComponent::CALCULATION_FIXED, 'is_basic' => true, 'sort_order' => 10],
            ['code' => 'housing', 'name_ar' => 'بدل السكن', 'component_type' => SalaryComponent::TYPE_EARNING, 'calculation_type' => SalaryComponent::CALCULATION_PERCENT_OF_BASIC, 'is_basic' => false, 'sort_order' => 20],
            ['code' => 'transport', 'name_ar' => 'بدل النقل', 'component_type' => SalaryComponent::TYPE_EARNING, 'calculation_type' => SalaryComponent::CALCULATION_FIXED, 'is_basic' => false, 'sort_order' => 30],
            ['code' => 'shift_allowance', 'name_ar' => 'بدل المناوبة', 'component_type' => SalaryComponent::TYPE_EARNING, 'calculation_type' => SalaryComponent::CALCULATION_FIXED, 'is_basic' => false, 'sort_order' => 40],
            ['code' => 'site_allowance', 'name_ar' => 'بدل الموقع', 'component_type' => SalaryComponent::TYPE_EARNING, 'calculation_type' => SalaryComponent::CALCULATION_FIXED, 'is_basic' => false, 'sort_order' => 50],
        ] as $salaryComponent) {
            SalaryComponent::query()->firstOrCreate(['code' => $salaryComponent['code']], $salaryComponent);
        }
    }
}
