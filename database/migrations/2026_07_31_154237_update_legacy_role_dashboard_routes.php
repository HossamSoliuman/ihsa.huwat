<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $dashboardRoutes = [
            'super_admin' => 'dashboard.admin',
            'government_admin' => 'government.dashboard',
            'region_manager' => 'dashboard.region-overview.index',
            'gov_supervisor' => 'dashboard.governorate-overview.index',
            'port_supervisor' => 'dashboard.port-operations.index',
            'stat_employee' => 'dashboard.employee-operations.index',
            'hr_manager' => 'dashboard.hr.index',
            'finance_officer' => 'dashboard.payroll.index',
            'quality_supervisor' => 'dashboard.discrepancies.index',
            'employee_portal' => 'dashboard.profile.show',
        ];

        foreach ($dashboardRoutes as $roleCode => $dashboardRoute) {
            DB::table('roles')
                ->where('code', $roleCode)
                ->update(['dashboard_route' => $dashboardRoute]);
        }
    }

    public function down(): void
    {
        $dashboardRoutes = [
            'super_admin' => 'admin.php',
            'government_admin' => 'government.dashboard',
            'region_manager' => 'region.php',
            'gov_supervisor' => 'governorate.php',
            'port_supervisor' => 'port.php',
            'stat_employee' => 'employee.php',
            'hr_manager' => 'hr.php',
            'finance_officer' => 'payroll.php',
            'quality_supervisor' => 'discrepancies.php',
            'employee_portal' => 'employment_profile.php',
        ];

        foreach ($dashboardRoutes as $roleCode => $dashboardRoute) {
            DB::table('roles')
                ->where('code', $roleCode)
                ->update(['dashboard_route' => $dashboardRoute]);
        }
    }
};
