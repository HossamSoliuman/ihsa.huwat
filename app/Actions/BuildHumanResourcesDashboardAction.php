<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Leave;
use App\Models\Port;
use App\Models\Shift;

class BuildHumanResourcesDashboardAction
{
    public function execute(): array
    {
        $employees = Employee::query()->with(['user:id,full_name,username', 'assignments' => fn ($query) => $query->with('port:id,name')->whereDate('assignment_date', today())])->get()->sortBy('user.full_name')->values();
        $expiringContracts = $employees->filter(fn (Employee $employee) => $employee->contract_end_date?->between(today(), today()->addDays(30)))->sortBy('contract_end_date')->values();
        $pendingLeaves = Leave::query()->with('employee.user:id,full_name')->where('status', 'pending')->oldest()->get();
        $todayAssignments = EmployeeAssignment::query()->with('port.governorate.region', 'employee')->whereDate('assignment_date', today())->get();
        $byGeo = $todayAssignments->groupBy(fn ($assignment) => $assignment->port_id)->map(fn ($rows) => [
            'port' => $rows->first()->port, 'employees_count' => $rows->pluck('employee_id')->unique()->count(),
        ])->values()->sortBy(fn (array $row) => $row['port']->name)->values();

        $kpi = [
            'total' => $employees->count(), 'active' => $employees->where('status', 'active')->count(),
            'permanent' => $employees->where('contract_type', 'permanent')->count(), 'temporary' => $employees->where('contract_type', 'temporary')->count(),
            'expiring' => $expiringContracts->count(),
            'on_leave' => Leave::query()->where('status', 'approved')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->distinct('employee_id')->count('employee_id'),
            'pending_leaves' => $pendingLeaves->count(),
            'new_this_month' => $employees->filter(fn (Employee $employee) => $employee->hire_date?->isSameMonth(today()))->count(),
        ];
        $ports = Port::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $shifts = Shift::query()->orderBy('start_time')->get();
        $assignableEmployees = $employees->whereIn('status', ['active', 'on_leave']);

        return compact('kpi', 'employees', 'expiringContracts', 'pendingLeaves', 'byGeo', 'ports', 'shifts', 'assignableEmployees');
    }
}
