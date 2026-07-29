<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Leave;
use App\Models\Port;
use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;

class BuildAttendanceDashboardAction
{
    public function execute(User $user, array $filters): array
    {
        $date = $filters['date'];
        $ports = Port::query()->visibleTo($user)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $selectedPortId = isset($filters['port_id']) ? (int) $filters['port_id'] : null;
        $scopedPortIds = $selectedPortId ? [$selectedPortId] : $ports->modelKeys();
        $shifts = Shift::query()->orderBy('start_time')->get();

        $assignments = EmployeeAssignment::query()->with([
            'employee.user:id,full_name',
            'employee.attendance' => fn ($query) => $query->whereDate('attendance_date', $date),
            'port:id,name',
            'shift:id,name,start_time,end_time',
        ])->whereDate('assignment_date', $date)->whereIn('port_id', $scopedPortIds)
            ->get()->sortBy([['shift.start_time', 'asc'], ['employee.user.full_name', 'asc']])->values();

        $rows = $assignments->map(function (EmployeeAssignment $assignment): array {
            $attendance = $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id);

            return ['assignment' => $assignment, 'attendance' => $attendance];
        });

        $kpi = [
            'present' => $rows->filter(fn (array $row) => in_array($row['attendance']?->status, ['present', 'late'], true))->count(),
            'absent' => $rows->where('attendance.status', 'absent')->count(),
            'late' => $rows->where('attendance.status', 'late')->count(),
            'on_leave' => Leave::query()->where('status', 'approved')->whereIn('employee_id', $assignments->pluck('employee_id')->unique())
                ->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->distinct('employee_id')->count('employee_id'),
            'morning' => $assignments->where('shift.name', 'morning')->count(),
            'evening' => $assignments->where('shift.name', 'evening')->count(),
            'night' => $assignments->where('shift.name', 'night')->count(),
            'overtime_hours' => round($rows->sum(fn (array $row) => $this->overtimeHours($row['assignment'], $row['attendance'])), 1),
        ];

        $coverageGaps = $ports->whereIn('id', $scopedPortIds)->flatMap(function (Port $port) use ($rows, $shifts): array {
            return $shifts->reject(fn (Shift $shift) => $rows->contains(fn (array $row) => $row['assignment']->port_id === $port->id
                && $row['assignment']->shift_id === $shift->id
                && in_array($row['attendance']?->status, ['present', 'late'], true)))
                ->map(fn (Shift $shift) => ['port' => $port, 'shift' => $shift])->all();
        })->values();

        $lateRows = $rows->where('attendance.status', 'late')->values();
        $substituteRows = $rows->filter(fn (array $row) => $row['assignment']->is_temporary)->values();
        $employees = Employee::query()->with('user:id,full_name')->where('status', 'active')
            ->whereDoesntHave('assignments', fn ($query) => $query->whereDate('assignment_date', $date))
            ->get()->sortBy('user.full_name')->values();

        return compact('filters', 'ports', 'shifts', 'rows', 'kpi', 'coverageGaps', 'lateRows', 'substituteRows', 'employees');
    }

    private function overtimeHours(EmployeeAssignment $assignment, mixed $attendance): float
    {
        if ($attendance?->check_in === null || $attendance->check_out === null) {
            return 0;
        }

        $start = Carbon::parse($assignment->assignment_date->toDateString().' '.$assignment->shift->start_time);
        $end = Carbon::parse($assignment->assignment_date->toDateString().' '.$assignment->shift->end_time);

        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
        }

        $scheduledMinutes = $start->diffInMinutes($end);
        $workedMinutes = $attendance->check_in->diffInMinutes($attendance->check_out);

        return max(0, $workedMinutes - $scheduledMinutes) / 60;
    }
}
