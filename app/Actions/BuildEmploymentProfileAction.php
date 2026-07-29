<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;

class BuildEmploymentProfileAction
{
    public function execute(User $user): array
    {
        $employee = Employee::query()->with([
            'employmentApplication.job.port:id,name,location_name',
            'employmentApplication.preferredPort:id,name,location_name',
            'assignments' => fn ($query) => $query->with('port:id,name,location_name', 'shift:id,name,start_time,end_time')
                ->whereDate('assignment_date', today())->latest('id'),
            'attendance' => fn ($query) => $query->with('shift:id,name,start_time,end_time')->latest('attendance_date')->latest('id')->limit(10),
            'payroll' => fn ($query) => $query->orderByDesc('period_year')->orderByDesc('period_month')->latest('id')->limit(1),
            'leaves' => fn ($query) => $query->latest()->limit(10),
        ])->where('user_id', $user->id)->first();
        $attendanceSummary = null;

        if ($employee !== null) {
            $attendanceSummary = Attendance::query()->where('employee_id', $employee->id)
                ->whereBetween('attendance_date', [today()->startOfMonth(), today()->endOfMonth()])->toBase()
                ->selectRaw("COUNT(DISTINCT attendance_date) AS recorded_days, COUNT(DISTINCT CASE WHEN status IN ('present', 'late') THEN attendance_date END) AS present_days, COUNT(DISTINCT CASE WHEN status = 'late' THEN attendance_date END) AS late_days, COUNT(DISTINCT CASE WHEN status = 'absent' THEN attendance_date END) AS absent_days, COUNT(DISTINCT CASE WHEN status = 'on_leave' THEN attendance_date END) AS leave_days")
                ->first();
        }

        return [
            'account' => $user,
            'employee' => $employee,
            'application' => $employee?->employmentApplication,
            'assignment' => $employee?->assignments->first(),
            'recentAttendance' => $employee?->attendance ?? collect(),
            'latestPayroll' => $employee?->payroll->first(),
            'leaveHistory' => $employee?->leaves ?? collect(),
            'attendanceSummary' => $attendanceSummary,
        ];
    }
}
