<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Leave;

class BuildHumanResourcesDashboardAction
{
    public function execute(): array
    {
        $employees = Employee::query()->with(['user:id,full_name,username', 'activeContract', 'assignments' => fn ($query) => $query->with('port:id,name')->whereDate('assignment_date', today())])->get()->sortBy('user.full_name')->values();
        $expiringContracts = $employees->filter(fn (Employee $employee) => $employee->activeContract?->end_date?->between(today(), today()->addDays(30)))->sortBy('activeContract.end_date')->values();
        $pendingLeaves = Leave::query()->with('employee.user:id,full_name')->where('status', 'pending')->oldest()->get();
        $todayAssignments = EmployeeAssignment::query()->with('port.governorate.region', 'employee')->whereDate('assignment_date', today())->get();
        $byGeo = $todayAssignments->groupBy(fn ($assignment) => $assignment->port_id)->map(fn ($rows) => [
            'port' => $rows->first()->port, 'employees_count' => $rows->pluck('employee_id')->unique()->count(),
        ])->values()->sortBy(fn (array $row) => $row['port']->name)->values();

        $kpi = [
            'total' => $employees->count(), 'active' => $employees->where('status', 'active')->count(),
            'permanent' => $employees->filter(fn (Employee $employee) => $employee->activeContract?->contract_type === 'permanent')->count(), 'temporary' => $employees->filter(fn (Employee $employee) => $employee->activeContract?->contract_type === 'temporary')->count(),
            'expiring' => $expiringContracts->count(),
            'on_leave' => Leave::query()->where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->distinct('employee_id')->count('employee_id'),
            'pending_leaves' => $pendingLeaves->count(),
            'new_this_month' => $employees->filter(fn (Employee $employee) => $employee->hire_date?->isSameMonth(today()))->count(),
        ];

        return compact('kpi', 'employees', 'expiringContracts', 'pendingLeaves', 'byGeo');
    }
}
