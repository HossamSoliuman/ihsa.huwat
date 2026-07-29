<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Port;
use App\Models\Region;
use App\Models\Shift;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BuildCoverageDashboardAction
{
    public function execute(User $user, array $filters): array
    {
        $regionId = isset($filters['region_id']) ? (int) $filters['region_id'] : null;
        $ports = Port::query()->visibleTo($user)->with([
            'governorate.region:id,name',
            'assignments' => fn ($query) => $query->whereDate('assignment_date', today())->with([
                'employee.user:id,full_name',
                'employee.attendance' => fn ($query) => $query->whereDate('attendance_date', today()),
                'shift:id,name,start_time,end_time',
            ]),
        ])->withCount([
            'trips as active_trips_count' => fn (Builder $query) => $query->whereIn('status', ['arrived', 'waiting_employee', 'counting']),
        ])->when($regionId, fn (Builder $query) => $query->whereHas('governorate', fn (Builder $query) => $query->where('region_id', $regionId)))
            ->orderBy('name')->get();

        $portRows = $ports->map(function (Port $port): array {
            $presentCount = $port->assignments->filter(fn ($assignment) => in_array(
                $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id)?->status,
                ['present', 'late'],
                true,
            ))->count();
            $status = ! $port->is_active ? 'inactive' : ($presentCount === 0 ? 'uncovered' : ($port->active_trips_count > $presentCount * 2 ? 'high_load' : 'covered'));

            return ['port' => $port, 'present_count' => $presentCount, 'active_trips' => $port->active_trips_count, 'status' => $status];
        });

        $activeEmployeeIds = Trip::query()->whereIn('port_id', $ports->modelKeys())
            ->whereIn('status', ['waiting_employee', 'counting'])->whereNotNull('assigned_employee_id')->pluck('assigned_employee_id');
        $presentEmployeeIds = $ports->flatMap->assignments->filter(fn ($assignment) => in_array(
            $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id)?->status,
            ['present', 'late'],
            true,
        ))->pluck('employee_id')->unique();

        $kpi = [
            'regions' => $ports->pluck('governorate.region_id')->unique()->count(),
            'governorates' => $ports->pluck('governorate_id')->unique()->count(),
            'ports' => $ports->count(),
            'covered' => $portRows->whereIn('status', ['covered', 'high_load'])->count(),
            'uncovered' => $portRows->where('status', 'uncovered')->count(),
            'high_load' => $portRows->where('status', 'high_load')->count(),
            'available_employees' => $presentEmployeeIds->diff($activeEmployeeIds)->count(),
            'temp_assignments' => $ports->flatMap->assignments->where('is_temporary', true)->count(),
        ];

        $portDetail = $portRows->first(fn (array $row) => $row['port']->id === (int) ($filters['port_detail'] ?? 0));

        if ($portDetail !== null) {
            $portDetail['expected_trips'] = Trip::query()->with('boat:id,name')->where('port_id', $portDetail['port']->id)
                ->where('status', 'expected')->orderBy('expected_arrival')->get();
            $portDetail['staff'] = $portDetail['port']->assignments->map(fn ($assignment) => [
                'assignment' => $assignment,
                'attendance' => $assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id),
            ]);
            $portDetail['suggestion'] = match ($portDetail['status']) {
                'uncovered' => 'يحتاج موظفًا واحدًا على الأقل فورًا لتغطية هذا الميناء اليوم.',
                'high_load' => 'يُقترح إضافة موظف إحصاء لتخفيف الضغط الحالي.',
                'inactive' => 'الميناء غير نشط حاليًا.',
                default => 'التغطية الحالية كافية.',
            };
        }

        $regions = Region::query()->when($user->role->code === 'region_manager', fn (Builder $query) => $query->whereKey($user->region_id))->orderBy('name')->get(['id', 'name']);
        $shifts = Shift::query()->orderBy('start_time')->get();
        $employees = Employee::query()->with('user:id,full_name')->where('status', 'active')
            ->whereDoesntHave('assignments', fn ($query) => $query->whereDate('assignment_date', today()))->get()->sortBy('user.full_name')->values();

        return compact('filters', 'regions', 'portRows', 'kpi', 'portDetail', 'shifts', 'employees');
    }
}
