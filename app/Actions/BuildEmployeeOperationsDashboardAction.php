<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\FishSpecies;
use App\Models\Trip;
use App\Models\User;

class BuildEmployeeOperationsDashboardAction
{
    public function execute(User $user): array
    {
        $employee = Employee::query()->with([
            'assignments' => fn ($query) => $query->with('port:id,name', 'shift:id,name,start_time,end_time')
                ->whereDate('assignment_date', today())->latest('id'),
        ])->where('user_id', $user->id)->first();
        $assignment = $employee?->assignments->first();
        $expectedTrips = collect();
        $arrivedTrips = collect();
        $countingTrips = collect();
        $fishSpecies = collect();
        $kpi = ['expected' => 0, 'arrived' => 0, 'counting' => 0, 'approved_today' => 0, 'difference_trips' => 0, 'total_weight' => 0, 'total_boxes' => 0, 'average_difference' => 0];

        if ($employee !== null && $assignment !== null) {
            $tripQuery = fn () => Trip::query()->with(['boat:id,name', 'captain:id,full_name'])->where('port_id', $assignment->port_id);
            $expectedTrips = $tripQuery()->where('status', 'expected')->orderBy('expected_arrival')->get();
            $arrivedTrips = $tripQuery()->whereIn('status', ['arrived', 'waiting_employee'])
                ->where(fn ($query) => $query->whereNull('assigned_employee_id')->orWhere('assigned_employee_id', $employee->id))
                ->orderBy('actual_arrival')->get();
            $countingTrips = $tripQuery()->where('status', 'counting')->where('assigned_employee_id', $employee->id)
                ->orderBy('counting_started_at')->get();
            $completedToday = Trip::query()->with(['catchDetails:id,trip_id,boxes_count', 'discrepancies:id,trip_id,diff_percent'])
                ->where('assigned_employee_id', $employee->id)->whereDate('counting_ended_at', today())->get();
            $differences = $completedToday->flatMap->discrepancies;
            $kpi = [
                'expected' => $expectedTrips->count(), 'arrived' => $arrivedTrips->count(), 'counting' => $countingTrips->count(),
                'approved_today' => $completedToday->whereIn('status', Trip::VERIFIED_STATUSES)->count(),
                'difference_trips' => $completedToday->filter(fn (Trip $trip) => $trip->discrepancies->isNotEmpty())->count(),
                'total_weight' => round((float) $completedToday->whereIn('status', Trip::VERIFIED_STATUSES)->sum('verified_weight'), 1),
                'total_boxes' => $completedToday->flatMap->catchDetails->sum('boxes_count'),
                'average_difference' => round((float) ($differences->avg('diff_percent') ?? 0), 1),
            ];
            $fishSpecies = FishSpecies::query()->orderBy('name_ar')->get(['id', 'name_ar']);
        }

        return compact('employee', 'assignment', 'expectedTrips', 'arrivedTrips', 'countingTrips', 'fishSpecies', 'kpi');
    }
}
