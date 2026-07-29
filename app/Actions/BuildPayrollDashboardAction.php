<?php

namespace App\Actions;

use App\Models\Payroll;

class BuildPayrollDashboardAction
{
    public function execute(int $month, int $year): array
    {
        $period = Payroll::query()->forPeriod($month, $year);
        $stats = (clone $period)->toBase()->selectRaw(
            "COUNT(*) AS total_count, COALESCE(SUM(base_salary), 0) AS base_total, COALESCE(SUM(allowances), 0) AS allowance_total, COALESCE(SUM(overtime_hours), 0) AS overtime_hours, COALESCE(SUM(overtime_amount), 0) AS overtime_total, COALESCE(SUM(bonuses), 0) AS bonus_total, COALESCE(SUM(deductions), 0) AS deduction_total, COALESCE(SUM(net_salary), 0) AS net_total, COALESCE(SUM(CASE WHEN paid_status = 'paid' THEN 1 ELSE 0 END), 0) AS paid_count"
        )->first();
        $payrollRows = (clone $period)->with('employee.user:id,full_name')->get()->sortBy('employee.user.full_name')->values();
        $monthlyComparison = Payroll::query()->toBase()
            ->selectRaw('period_month, period_year, SUM(net_salary) AS total_net')
            ->groupBy('period_year', 'period_month')->orderByDesc('period_year')->orderByDesc('period_month')->limit(6)->get();

        return compact('month', 'year', 'stats', 'payrollRows', 'monthlyComparison');
    }
}
