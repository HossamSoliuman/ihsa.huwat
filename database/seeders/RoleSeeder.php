<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            ['code' => 'super_admin', 'name_ar' => 'الإدارة العليا', 'dashboard_route' => 'dashboard.admin'],
            ['code' => 'government_admin', 'name_ar' => 'مسؤول البوابة الحكومية', 'dashboard_route' => 'government.dashboard'],
            ['code' => 'region_manager', 'name_ar' => 'مدير المنطقة', 'dashboard_route' => 'dashboard.region-overview.index'],
            ['code' => 'gov_supervisor', 'name_ar' => 'مشرف المحافظة', 'dashboard_route' => 'dashboard.governorate-overview.index'],
            ['code' => 'port_supervisor', 'name_ar' => 'مشرف الميناء', 'dashboard_route' => 'dashboard.port-operations.index'],
            ['code' => 'stat_employee', 'name_ar' => 'موظف الإحصاء', 'dashboard_route' => 'dashboard.employee-operations.index'],
            ['code' => 'hr_manager', 'name_ar' => 'مدير الموارد البشرية', 'dashboard_route' => 'dashboard.hr.index'],
            ['code' => 'finance_officer', 'name_ar' => 'مسؤول الرواتب والمالية', 'dashboard_route' => 'dashboard.payroll.index'],
            ['code' => 'quality_supervisor', 'name_ar' => 'مراقب الجودة', 'dashboard_route' => 'dashboard.discrepancies.index'],
            ['code' => 'employee_portal', 'name_ar' => 'بوابة الموظف', 'dashboard_route' => 'dashboard.profile.show'],
        ] as $role) {
            Role::query()->updateOrCreate(['code' => $role['code']], $role);
        }
    }
}
