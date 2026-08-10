<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Port;
use App\Models\Shift;
use App\Models\Trip;
use App\Models\User;

class BuildPortOperationsAction
{
    public function execute(User $user, array $filters): array
    {
        $ports = Port::query()->visibleTo($user)->where('is_active', true)->with('governorate:id,name')->orderBy('name')->get();
        $port = $ports->firstWhere('id', (int) ($filters['port_id'] ?? 0)) ?? $ports->first();

        if (! $port) {
            return compact('ports', 'port') + $this->emptyState();
        }

        $port->load(['assignments' => fn ($query) => $query->whereDate('assignment_date', today())->with([
            'employee.user:id,full_name', 'shift:id,name,start_time,end_time',
            'employee.attendance' => fn ($query) => $query->whereDate('attendance_date', today()),
            'employee.assignedTrips' => fn ($query) => $query->whereIn('status', ['waiting_employee', 'counting'])->latest('id'),
        ])]);
        $staff = $port->assignments->map(function ($assignment): array {
            $attendance = $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id);

            return [
                'assignment' => $assignment,
                'attendance' => $attendance,
                'current_trip' => $assignment->employee->assignedTrips->first(),
                'trips_today' => $assignment->employee->assignedTrips()->whereDate('created_at', today())->count(),
            ];
        })->sortBy(fn (array $row) => $row['assignment']->shift->start_time)->values();
        $availableStaff = $staff->filter(fn (array $row) => in_array($row['attendance']?->status, ['present', 'late'], true) && $row['current_trip'] === null)->values();
        $expectedTrips = Trip::query()->with(['boat:id,name', 'captain:id,full_name'])->where('port_id', $port->id)->where('status', 'expected')->orderBy('expected_arrival')->get();
        $arrivedTrips = Trip::query()->with(['boat:id,name', 'captain:id,full_name', 'assignedEmployee.user:id,full_name', 'discrepancies:id,trip_id,review_status'])->where('port_id', $port->id)->whereIn('status', ['arrived', 'waiting_employee', 'counting'])->orderBy('actual_arrival')->get();
        $reviewTrips = Trip::query()->with(['boat:id,name', 'captain:id,full_name', 'discrepancies' => fn ($query) => $query->where('review_status', '!=', 'approved')->latest('id')])
            ->where('port_id', $port->id)->where('status', 'pending_review')->whereHas('discrepancies', fn ($query) => $query->where('review_status', '!=', 'approved'))->orderBy('actual_arrival')->get();
        $waitTimes = $arrivedTrips->filter(fn (Trip $trip) => $trip->actual_arrival && $trip->counting_started_at)
            ->map(fn (Trip $trip) => $trip->actual_arrival->diffInMinutes($trip->counting_started_at));
        $kpi = [
            'on_shift' => $staff->count(), 'available' => $availableStaff->count(),
            'busy' => $staff->whereNotNull('current_trip')->count(), 'expected' => $expectedTrips->count(),
            'arrived' => $arrivedTrips->whereIn('status', ['arrived', 'waiting_employee'])->count(),
            'counting' => $arrivedTrips->where('status', 'counting')->count(), 'pending_review' => $reviewTrips->count(),
            'average_wait' => $waitTimes->isEmpty() ? 0 : (int) round($waitTimes->average()),
        ];
        $shifts = Shift::query()->active()->orderBy('start_time')->get();
        $employees = Employee::query()->with('user:id,full_name')->where('status', 'active')
            ->whereDoesntHave('assignments', fn ($query) => $query->whereDate('assignment_date', today()))
            ->get()->sortBy('user.full_name')->values();

        return compact('ports', 'port', 'staff', 'availableStaff', 'expectedTrips', 'arrivedTrips', 'reviewTrips', 'shifts', 'employees', 'kpi');
    }

    private function emptyState(): array
    {
        $empty = collect();

        return ['staff' => $empty, 'availableStaff' => $empty, 'expectedTrips' => $empty, 'arrivedTrips' => $empty, 'reviewTrips' => $empty, 'shifts' => $empty, 'employees' => $empty, 'kpi' => []];
    }
}
