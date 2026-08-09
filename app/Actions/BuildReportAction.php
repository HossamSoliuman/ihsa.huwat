<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Boat;
use App\Models\Captain;
use App\Models\CatchDetail;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\FishSpecies;
use App\Models\Governorate;
use App\Models\Leave;
use App\Models\PayrollRunEmployee;
use App\Models\Port;
use App\Models\Region;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildReportAction
{
    public function execute(User $user, array $filters): array
    {
        $portIds = $this->portIds($user, $filters);
        $employeeIds = Employee::query()->whereHas('assignments', fn (Builder $query) => $query->whereIn('port_id', $portIds))->pluck('id');
        $report = match ($filters['report_type']) {
            'catch' => $this->catchReport($portIds, $filters),
            'discrepancies' => $this->discrepancyReport($portIds, $filters),
            'employees' => $this->employeeReport($portIds, $filters),
            'ports' => $this->portReport($portIds, $filters),
            'attendance' => $this->attendanceReport($portIds, $filters),
            'shifts' => $this->shiftReport($portIds, $filters),
            'leaves' => $this->leaveReport($employeeIds, $filters),
            'payroll' => $this->payrollReport($employeeIds, $filters),
            'coverage' => $this->coverageReport($portIds),
            'species' => $this->speciesReport($portIds, $filters),
            'boats' => $this->boatReport($portIds, $filters),
            default => $this->tripReport($portIds, $filters),
        };

        return $report + $this->filterOptions($user, $portIds, $employeeIds) + [
            'filters' => $filters,
            'reportTypes' => config('reports.types'),
            'reportTitle' => config("reports.types.{$filters['report_type']}"),
        ];
    }

    private function tripReport(Collection $portIds, array $filters): array
    {
        $trips = $this->tripQuery($portIds, $filters)->with(['port:id,name', 'boat:id,name', 'captain:id,full_name'])->limit(config('reports.limit'))->get();

        return $this->table(['الرحلة', 'الميناء', 'القارب', 'الكابتن', 'الحالة', 'الوصول الفعلي', 'الوزن المبلغ', 'الوزن الفعلي'], $trips->map(fn (Trip $trip) => [
            $trip->trip_code, $trip->port->name, $trip->boat->name, $trip->captain->full_name,
            config("reports.trip_statuses.{$trip->status}", $trip->status), $this->dateTime($trip->actual_arrival),
            $this->number($trip->captain_reported_weight), $this->number($trip->verified_weight),
        ]));
    }

    private function catchReport(Collection $portIds, array $filters): array
    {
        $trips = $this->tripQuery($portIds, $filters)->with('port:id,name')->whereIn('status', Trip::VERIFIED_STATUSES)
            ->when($filters['species_id'] ?? null, fn (Builder $query, $id) => $query->whereHas('catchDetails', fn (Builder $query) => $query->where('species_id', $id)))
            ->limit(config('reports.limit'))->get();

        return $this->table(['الرحلة', 'الميناء', 'تاريخ الاعتماد', 'الكمية المعتمدة (كجم)'], $trips->map(fn (Trip $trip) => [$trip->trip_code, $trip->port->name, $this->dateTime($trip->approved_at), $this->number($trip->verified_weight)]));
    }

    private function discrepancyReport(Collection $portIds, array $filters): array
    {
        $rows = TripDiscrepancy::query()->with('trip.port:id,name')->whereHas('trip', fn (Builder $query) => $this->applyTripPeriod($query->whereIn('port_id', $portIds), $filters))
            ->when(isset($filters['diff_min']), fn (Builder $query) => $query->whereRaw('ABS(diff_percent) >= ?', [$filters['diff_min']]))
            ->when(isset($filters['diff_max']), fn (Builder $query) => $query->whereRaw('ABS(diff_percent) <= ?', [$filters['diff_max']]))
            ->orderByDesc('diff_percent')->limit(config('reports.limit'))->get()->map(fn (TripDiscrepancy $item) => [
                $item->trip->trip_code, $item->trip->port->name, $this->number($item->diff_kg), $this->number($item->diff_percent, 1).'%',
                config("discrepancies.severities.{$item->severity}", $item->severity), config("reports.review_statuses.{$item->review_status}", $item->review_status),
            ]);

        return $this->table(['الرحلة', 'الميناء', 'الفرق (كجم)', 'النسبة', 'التصنيف', 'حالة المراجعة'], $rows);
    }

    private function employeeReport(Collection $portIds, array $filters): array
    {
        $trips = $this->tripQuery($portIds, $filters)->with(['assignedEmployee.user:id,full_name', 'discrepancies:id,trip_id,diff_percent'])
            ->whereIn('status', Trip::VERIFIED_STATUSES)->whereNotNull('assigned_employee_id')->get();
        $rows = $trips->groupBy('assigned_employee_id')->map(function (Collection $items): array {
            $differences = $items->flatMap->discrepancies;

            return [$items->first()->assignedEmployee->user->full_name, $items->count(), $this->number($items->sum('verified_weight')), $this->number($differences->avg('diff_percent'), 1).'%'];
        })->sortByDesc(1)->take(config('reports.limit'))->values();

        return $this->table(['الموظف', 'عدد الرحلات', 'الكمية (كجم)', 'متوسط الفرق'], $rows);
    }

    private function portReport(Collection $portIds, array $filters): array
    {
        $trips = $this->tripQuery($portIds, $filters)->with(['port:id,name', 'discrepancies:id,trip_id,diff_percent'])->whereIn('status', Trip::VERIFIED_STATUSES)->get();
        $rows = $trips->groupBy('port_id')->map(function (Collection $items): array {
            $differences = $items->flatMap->discrepancies;

            return [$items->first()->port->name, $items->count(), $this->number($items->sum('verified_weight')), $this->number($differences->avg('diff_percent'), 1).'%'];
        })->sortByDesc(1)->take(config('reports.limit'))->values();

        return $this->table(['الميناء', 'عدد الرحلات', 'الكمية المعتمدة (كجم)', 'متوسط الفرق'], $rows);
    }

    private function attendanceReport(Collection $portIds, array $filters): array
    {
        $assignments = EmployeeAssignment::query()->with(['port:id,name', 'employee.user:id,full_name', 'shift:id,name'])
            ->whereIn('port_id', $portIds)->whereBetween('assignment_date', [$filters['date_from'], $filters['date_to']])
            ->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->where('employee_id', $id))->get();
        $attendance = Attendance::query()->whereIn('employee_id', $assignments->pluck('employee_id'))->whereBetween('attendance_date', [$filters['date_from'], $filters['date_to']])->get()->keyBy(fn (Attendance $item) => "{$item->employee_id}:{$item->shift_id}:{$item->attendance_date->toDateString()}");
        $rows = $assignments->map(function (EmployeeAssignment $assignment) use ($attendance): array {
            $item = $attendance->get("{$assignment->employee_id}:{$assignment->shift_id}:{$assignment->assignment_date->toDateString()}");

            return [$assignment->employee->user->full_name, $assignment->port->name, $assignment->assignment_date->toDateString(), $assignment->shift->name, $item ? config("attendance.statuses.{$item->status}") : 'لم يبدأ', $this->dateTime($item?->check_in), $this->dateTime($item?->check_out)];
        })->take(config('reports.limit'));

        return $this->table(['الموظف', 'الميناء', 'التاريخ', 'المناوبة', 'الحالة', 'الحضور', 'الانصراف'], $rows);
    }

    private function shiftReport(Collection $portIds, array $filters): array
    {
        $rows = EmployeeAssignment::query()->with(['port:id,name', 'employee.user:id,full_name', 'shift:id,name'])->whereIn('port_id', $portIds)
            ->whereBetween('assignment_date', [$filters['date_from'], $filters['date_to']])->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->where('employee_id', $id))
            ->latest('assignment_date')->limit(config('reports.limit'))->get()->map(fn (EmployeeAssignment $item) => [$item->employee->user->full_name, $item->port->name, $item->assignment_date->toDateString(), $item->shift->name, $item->is_temporary ? 'نعم' : 'لا']);

        return $this->table(['الموظف', 'الميناء', 'التاريخ', 'المناوبة', 'بديل؟'], $rows);
    }

    private function leaveReport(Collection $employeeIds, array $filters): array
    {
        $rows = Leave::query()->with('employee.user:id,full_name')->whereIn('employee_id', $employeeIds)->whereDate('start_date', '<=', $filters['date_to'])->whereDate('end_date', '>=', $filters['date_from'])
            ->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->where('employee_id', $id))->latest('start_date')->limit(config('reports.limit'))->get()
            ->map(fn (Leave $item) => [$item->employee->user->full_name, $item->start_date->toDateString(), $item->end_date->toDateString(), config("employment.leave_statuses.{$item->status}", $item->status), $item->reason ?: '—']);

        return $this->table(['الموظف', 'من', 'إلى', 'الحالة', 'السبب'], $rows);
    }

    private function payrollReport(Collection $employeeIds, array $filters): array
    {
        $from = CarbonImmutable::parse($filters['date_from'])->startOfMonth();
        $to = CarbonImmutable::parse($filters['date_to'])->startOfMonth();
        $rows = PayrollRunEmployee::query()
            ->with('payrollRun:id,period_year,period_month,status')
            ->whereIn('employee_id', $employeeIds)
            ->whereHas('payrollRun', fn (Builder $query) => $query->whereRaw(
                '(period_year * 100 + period_month) BETWEEN ? AND ?',
                [(int) $from->format('Ym'), (int) $to->format('Ym')],
            ))
            ->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->where('employee_id', $id))
            ->latest('payroll_run_id')
            ->limit(config('reports.limit'))
            ->get()
            ->map(fn (PayrollRunEmployee $item) => [
                $item->employee_name,
                "{$item->payrollRun->period_month}/{$item->payrollRun->period_year}",
                $this->number($item->total_earnings),
                $this->number($item->total_deductions),
                $this->number($item->net_salary),
                config("payroll.run_statuses.{$item->payrollRun->status}", $item->payrollRun->status),
            ]);

        return $this->table(['الموظف', 'الشهر/السنة', 'الاستحقاقات', 'الخصومات', 'الصافي', 'حالة المسير'], $rows);
    }

    private function coverageReport(Collection $portIds): array
    {
        $ports = Port::query()->with(['governorate:id,name', 'assignments' => fn ($query) => $query->whereDate('assignment_date', today())->with(['employee.attendance' => fn ($query) => $query->whereDate('attendance_date', today())])])
            ->withCount(['trips as active_trips_count' => fn (Builder $query) => $query->whereIn('status', ['arrived', 'waiting_employee', 'counting'])])->whereIn('id', $portIds)->get();
        $rows = $ports->map(function (Port $port): array {
            $present = $port->assignments->filter(fn ($assignment) => in_array($assignment->employee->attendance->firstWhere('shift_id', $assignment->shift_id)?->status, ['present', 'late'], true))->count();
            $status = ! $port->is_active ? 'غير نشط' : ($present === 0 ? 'غير مغطى' : ($port->active_trips_count > $present * 2 ? 'ضغط مرتفع' : 'مغطى'));

            return [$port->name, $port->governorate->name, $port->is_active ? 'نعم' : 'لا', $present, $port->active_trips_count, $status];
        });

        return $this->table(['الميناء', 'المحافظة', 'نشط؟', 'موظفون حاضرون اليوم', 'قوارب نشطة الآن', 'الحالة'], $rows);
    }

    private function speciesReport(Collection $portIds, array $filters): array
    {
        $details = CatchDetail::query()->with('species:id,name_ar')->whereHas('trip', fn (Builder $query) => $this->applyTripPeriod($query->whereIn('port_id', $portIds)->whereIn('status', Trip::VERIFIED_STATUSES), $filters))
            ->when($filters['species_id'] ?? null, fn (Builder $query, $id) => $query->where('species_id', $id))->get();
        $rows = $details->groupBy('species_id')->map(fn (Collection $items) => [$items->first()->species->name_ar, $items->pluck('trip_id')->unique()->count(), $this->number($items->sum('verified_kg'))])->sortByDesc(2)->take(config('reports.limit'))->values();

        return $this->table(['النوع', 'عدد الرحلات', 'إجمالي الكمية المعتمدة (كجم)'], $rows);
    }

    private function boatReport(Collection $portIds, array $filters): array
    {
        $trips = $this->tripQuery($portIds, $filters)->with(['boat:id,name', 'captain:id,full_name', 'discrepancies:id,trip_id,diff_percent'])->whereIn('status', Trip::VERIFIED_STATUSES)->get();
        $rows = $trips->groupBy(fn (Trip $trip) => "{$trip->boat_id}:{$trip->captain_id}")->map(function (Collection $items): array {
            $first = $items->first();

            return [$first->boat->name, $first->captain->full_name, $items->count(), $this->number($items->sum('verified_weight')), $this->number($items->flatMap->discrepancies->avg('diff_percent'), 1).'%'];
        })->sortByDesc(2)->take(config('reports.limit'))->values();

        return $this->table(['القارب', 'الكابتن', 'عدد الرحلات', 'إجمالي الكمية (كجم)', 'متوسط الفرق'], $rows);
    }

    private function tripQuery(Collection $portIds, array $filters): Builder
    {
        return $this->applyTripPeriod(Trip::query()->whereIn('port_id', $portIds), $filters)
            ->when($filters['boat_id'] ?? null, fn (Builder $query, $id) => $query->where('boat_id', $id))
            ->when($filters['captain_id'] ?? null, fn (Builder $query, $id) => $query->where('captain_id', $id))
            ->when($filters['employee_id'] ?? null, fn (Builder $query, $id) => $query->where('assigned_employee_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))->latest('actual_arrival');
    }

    private function applyTripPeriod(Builder $query, array $filters): Builder
    {
        return $query->where(function (Builder $query) use ($filters): void {
            $query->whereBetween('actual_arrival', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59'])
                ->orWhere(function (Builder $query) use ($filters): void {
                    $query->whereNull('actual_arrival')->whereBetween('expected_arrival', [$filters['date_from'].' 00:00:00', $filters['date_to'].' 23:59:59']);
                });
        });
    }

    private function portIds(User $user, array $filters): Collection
    {
        return Port::query()->visibleTo($user)
            ->when($filters['port_id'] ?? null, fn (Builder $query, $id) => $query->whereKey($id))
            ->when(! ($filters['port_id'] ?? null) && ($filters['governorate_id'] ?? null), fn (Builder $query) => $query->where('governorate_id', $filters['governorate_id']))
            ->when(! ($filters['port_id'] ?? null) && ! ($filters['governorate_id'] ?? null) && ($filters['region_id'] ?? null), fn (Builder $query) => $query->whereHas('governorate', fn (Builder $query) => $query->where('region_id', $filters['region_id'])))
            ->pluck('id');
    }

    private function filterOptions(User $user, Collection $portIds, Collection $employeeIds): array
    {
        $regions = Region::query()->when($user->role->code === 'region_manager', fn (Builder $query) => $query->whereKey($user->region_id))->when($user->role->code === 'gov_supervisor', fn (Builder $query) => $query->whereHas('governorates', fn (Builder $query) => $query->whereKey($user->governorate_id)))->orderBy('name')->get(['id', 'name']);
        $governorates = Governorate::query()->when($user->role->code === 'region_manager', fn (Builder $query) => $query->where('region_id', $user->region_id))->when($user->role->code === 'gov_supervisor', fn (Builder $query) => $query->whereKey($user->governorate_id))->orderBy('name')->get(['id', 'name']);

        return [
            'regions' => $regions, 'governorates' => $governorates,
            'ports' => Port::query()->visibleTo($user)->orderBy('name')->get(['id', 'name']),
            'boats' => Boat::query()->whereHas('trips', fn (Builder $query) => $query->whereIn('port_id', $portIds))->orderBy('name')->get(['id', 'name']),
            'captains' => Captain::query()->whereHas('trips', fn (Builder $query) => $query->whereIn('port_id', $portIds))->orderBy('full_name')->get(['id', 'full_name']),
            'employees' => Employee::query()->with('user:id,full_name')->whereIn('id', $employeeIds)->get()->sortBy('user.full_name')->values(),
            'species' => FishSpecies::query()->orderBy('name_ar')->get(['id', 'name_ar']),
        ];
    }

    private function table(array $columns, Collection $rows): array
    {
        return ['columns' => $columns, 'rows' => $rows->values(), 'isLimited' => $rows->count() >= config('reports.limit')];
    }

    private function dateTime(mixed $value): string
    {
        return $value ? CarbonImmutable::parse($value)->format('Y/m/d H:i') : '—';
    }

    private function number(mixed $value, int $precision = 2): string
    {
        return number_format((float) ($value ?? 0), $precision, '.', ',');
    }
}
