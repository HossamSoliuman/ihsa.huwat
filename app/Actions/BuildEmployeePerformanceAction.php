<?php

namespace App\Actions;

use App\Models\EmployeeAssignment;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Collection;

class BuildEmployeePerformanceAction
{
    public function execute(User $user, array $filters): array
    {
        $trips = Trip::query()->visibleTo($user)->with([
            'assignedEmployee.user:id,full_name',
            'port:id,name',
            'discrepancies:id,trip_id,diff_percent',
            'attachments:id,trip_id,type',
        ])->whereNotNull('assigned_employee_id')->whereIn('status', Trip::VERIFIED_STATUSES)
            ->whereBetween('actual_arrival', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])->get();

        $employeeIds = $trips->pluck('assigned_employee_id')->unique();
        $lastPorts = EmployeeAssignment::query()->with('port:id,name')->whereIn('employee_id', $employeeIds)
            ->orderByDesc('assignment_date')->orderByDesc('id')->get()->groupBy('employee_id')->map->first();

        $performanceRows = $trips->groupBy('assigned_employee_id')->map(function (Collection $employeeTrips, int $employeeId) use ($lastPorts): array {
            $timedTrips = $employeeTrips->filter(fn (Trip $trip) => $trip->counting_started_at !== null && $trip->counting_ended_at !== null);
            $discrepancies = $employeeTrips->flatMap->discrepancies;
            $tripsWithAttachments = $employeeTrips->filter(fn (Trip $trip) => $trip->attachments->isNotEmpty())->count();
            $averageDifference = $discrepancies->avg('diff_percent');
            $edits = $employeeTrips->where('edited_after_approval', true)->count();

            return [
                'employee' => $employeeTrips->first()->assignedEmployee,
                'last_port' => $lastPorts->get($employeeId)?->port?->name,
                'trips_count' => $employeeTrips->count(),
                'total_weight' => round((float) $employeeTrips->sum('verified_weight'), 1),
                'average_minutes' => $timedTrips->isEmpty() ? null : round((float) $timedTrips->avg(fn (Trip $trip) => $trip->counting_started_at->diffInMinutes($trip->counting_ended_at)), 1),
                'edits_after_approval' => $edits,
                'difference_trips' => $employeeTrips->filter(fn (Trip $trip) => $trip->discrepancies->isNotEmpty())->count(),
                'average_difference' => $averageDifference === null ? null : round((float) $averageDifference, 1),
                'attachment_completion' => round($tripsWithAttachments / $employeeTrips->count() * 100, 1),
                ...$this->rating((float) ($averageDifference ?? 0), $edits),
            ];
        })->sortByDesc('trips_count')->values();

        $timedAverages = $performanceRows->pluck('average_minutes')->filter(fn ($value) => $value !== null);
        $kpi = [
            'trips' => $performanceRows->sum('trips_count'),
            'weight' => round((float) $performanceRows->sum('total_weight'), 1),
            'average_minutes' => $timedAverages->isEmpty() ? 0 : round((float) $timedAverages->avg(), 1),
            'difference_trips' => $performanceRows->sum('difference_trips'),
            'attachment_completion' => $performanceRows->isEmpty() ? 0 : round((float) $performanceRows->avg('attachment_completion'), 1),
            'edits' => $performanceRows->sum('edits_after_approval'),
            'top_performer' => $performanceRows->first()['employee']->user->full_name ?? null,
            'top_quality' => $performanceRows->sortBy(fn (array $row) => $row['average_difference'] ?? PHP_FLOAT_MAX)->first()['employee']->user->full_name ?? null,
        ];

        return compact('filters', 'performanceRows', 'kpi');
    }

    private function rating(float $averageDifference, int $edits): array
    {
        return match (true) {
            $averageDifference <= 3 && $edits === 0 => ['rating' => 'ممتاز', 'rating_tone' => 'excellent'],
            $averageDifference <= 5 && $edits <= 1 => ['rating' => 'جيد جدًا', 'rating_tone' => 'good'],
            $averageDifference <= 10 => ['rating' => 'جيد', 'rating_tone' => 'fair'],
            default => ['rating' => 'يحتاج تحسين', 'rating_tone' => 'poor'],
        };
    }
}
