<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\CatchDetail;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Port;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class BuildAlertsDashboardAction
{
    /**
     * @return array{alerts: Collection<int, array{type: string, message: string, severity: string, time: mixed}>, countByType: Collection<string, int>, criticalCount: int, warningCount: int, monitoredPortsCount: int}
     */
    public function execute(User $user): array
    {
        $ports = Port::query()->visibleTo($user)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $portIds = $ports->modelKeys();
        $alerts = collect();

        if ($portIds !== []) {
            $this->addWaitingTripAlerts($alerts, $user, $portIds);
            $this->addDiscrepancyAlerts($alerts, $user, $portIds);
            $this->addUnreportedCatchAlerts($alerts, $user, $portIds);
            $this->addMissingAttachmentAlerts($alerts, $user, $portIds);
            $this->addCoverageAlerts($alerts, $portIds);
            $this->addCongestionAlerts($alerts, $portIds);
            $this->addEditedTripAlerts($alerts, $user, $portIds);
        }

        if ($user->role->code !== 'port_supervisor') {
            $this->addContractAlerts($alerts);
        }

        $severityOrder = ['critical' => 0, 'warning' => 1, 'info' => 2];
        $alerts = $alerts->sortBy([
            fn (array $alert): int => $severityOrder[$alert['severity']] ?? 3,
            fn (array $alert): int => -$alert['time']->getTimestamp(),
        ])->values();
        $countByType = $alerts->countBy('type');

        return [
            'alerts' => $alerts,
            'countByType' => $countByType,
            'criticalCount' => $alerts->where('severity', 'critical')->count(),
            'warningCount' => $alerts->where('severity', 'warning')->count(),
            'monitoredPortsCount' => count($portIds),
        ];
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addWaitingTripAlerts(Collection $alerts, User $user, array $portIds): void
    {
        $unassignedCutoff = now()->subMinutes(config('alerts.unassigned_wait_minutes'));
        $assignedCutoff = now()->subMinutes(config('alerts.assigned_wait_minutes'));

        Trip::query()->visibleTo($user)->with('port:id,name')->whereIn('port_id', $portIds)
            ->whereIn('status', ['arrived', 'waiting_employee'])->whereNotNull('actual_arrival')
            ->where('actual_arrival', '<=', $unassignedCutoff)->get()
            ->each(function (Trip $trip) use ($alerts, $assignedCutoff): void {
                if ($trip->assigned_employee_id === null) {
                    $alerts->push($this->alert(
                        'قارب وصل ولم يبدأ إحصاؤه',
                        "الرحلة {$trip->trip_code} بميناء {$trip->port->name} بانتظار إسناد موظف",
                        'critical',
                        $trip->actual_arrival,
                    ));

                    return;
                }

                if ($trip->actual_arrival->lessThanOrEqualTo($assignedCutoff)) {
                    $alerts->push($this->alert(
                        'رحلة تجاوزت وقت الانتظار',
                        "الرحلة {$trip->trip_code} بميناء {$trip->port->name} تجاوزت وقت الانتظار المسموح",
                        'warning',
                        $trip->actual_arrival,
                    ));
                }
            });
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addDiscrepancyAlerts(Collection $alerts, User $user, array $portIds): void
    {
        TripDiscrepancy::query()->visibleTo($user)->with('trip.port:id,name')
            ->whereHas('trip', fn (Builder $query) => $query->whereIn('port_id', $portIds))
            ->where('severity', 'major')->where('review_status', '<>', 'approved')->get()
            ->each(function (TripDiscrepancy $discrepancy) use ($alerts): void {
                $percent = number_format((float) $discrepancy->diff_percent, 1);
                $alerts->push($this->alert(
                    'فرق تجاوز الحد المسموح',
                    "الرحلة {$discrepancy->trip->trip_code} بميناء {$discrepancy->trip->port->name} بفرق {$percent}% بانتظار اعتماد المشرف",
                    'critical',
                    $discrepancy->reviewed_at ?? $discrepancy->trip->actual_arrival ?? $discrepancy->trip->created_at,
                ));
            });
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addUnreportedCatchAlerts(Collection $alerts, User $user, array $portIds): void
    {
        CatchDetail::query()->with(['species:id,name_ar', 'trip.port:id,name'])
            ->where('is_unreported_by_captain', true)
            ->whereHas('trip', fn (Builder $query) => $query->visibleTo($user)->whereIn('port_id', $portIds)
                ->where('actual_arrival', '>=', now()->subDays(config('alerts.unreported_window_days'))))
            ->get()->each(function (CatchDetail $detail) use ($alerts): void {
                $alerts->push($this->alert(
                    'صنف غير مسجل من الكابتن',
                    "الرحلة {$detail->trip->trip_code} بميناء {$detail->trip->port->name}: صنف ({$detail->species->name_ar}) لم يدخله الكابتن",
                    'warning',
                    $detail->trip->actual_arrival,
                ));
            });
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addMissingAttachmentAlerts(Collection $alerts, User $user, array $portIds): void
    {
        Trip::query()->visibleTo($user)->with(['port:id,name', 'attachments:id,trip_id,type'])
            ->whereIn('port_id', $portIds)->whereIn('status', Trip::VERIFIED_STATUSES)
            ->whereBetween('approved_at', [today()->startOfDay(), today()->endOfDay()])->get()
            ->each(function (Trip $trip) use ($alerts): void {
                $types = $trip->attachments->pluck('type');

                if (! $types->contains('scale_photo')) {
                    $alerts->push($this->alert('صورة الميزان غير مرفقة', "الرحلة {$trip->trip_code} بميناء {$trip->port->name} معتمدة بدون صورة ميزان", 'warning', $trip->approved_at));
                }

                if (! $types->contains('captain_signature')) {
                    $alerts->push($this->alert('توقيع الكابتن غير موجود', "الرحلة {$trip->trip_code} بميناء {$trip->port->name} معتمدة بدون توقيع الكابتن", 'warning', $trip->approved_at));
                }
            });
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addCoverageAlerts(Collection $alerts, array $portIds): void
    {
        $temporaryCoverage = EmployeeAssignment::query()->whereIn('port_id', $portIds)
            ->whereDate('assignment_date', today())->where('is_temporary', true)->get(['port_id', 'shift_id'])
            ->mapWithKeys(fn (EmployeeAssignment $assignment) => ["{$assignment->port_id}:{$assignment->shift_id}" => true]);

        Attendance::query()->with([
            'employee.user:id,full_name',
            'employee.assignments' => fn ($query) => $query->with('port:id,name', 'shift:id,name')->whereIn('port_id', $portIds)->whereDate('assignment_date', today()),
        ])->whereDate('attendance_date', today())->where('status', 'absent')
            ->whereHas('employee.assignments', fn (Builder $query) => $query->whereIn('port_id', $portIds)->whereDate('assignment_date', today()))
            ->get()->each(function (Attendance $attendance) use ($alerts, $temporaryCoverage): void {
                $assignment = $attendance->employee->assignments->first();

                if ($assignment === null || $temporaryCoverage->has("{$assignment->port_id}:{$assignment->shift_id}")) {
                    return;
                }

                $alerts->push($this->alert(
                    'موظف غائب دون بديل',
                    "الموظف {$attendance->employee->user->full_name} غائب اليوم بميناء {$assignment->port->name} ({$assignment->shift->name}) بدون بديل",
                    'critical',
                    today(),
                ));
            });

        Port::query()->whereKey($portIds)->where('is_active', true)
            ->whereDoesntHave('assignments', fn (Builder $query) => $query->whereDate('assignment_date', today()))
            ->get(['id', 'name'])->each(fn (Port $port) => $alerts->push($this->alert(
                'ميناء غير مغطى',
                "الميناء {$port->name} بدون أي موظف إحصاء مسند لليوم",
                'critical',
                today(),
            )));
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addCongestionAlerts(Collection $alerts, array $portIds): void
    {
        Port::query()->whereKey($portIds)->withCount([
            'trips as active_trips_count' => fn (Builder $query) => $query->whereIn('status', ['arrived', 'waiting_employee', 'counting']),
        ])->get(['id', 'name'])
            ->where('active_trips_count', '>=', config('alerts.congestion_threshold'))
            ->each(fn (Port $port) => $alerts->push($this->alert(
                'ازدحام قوارب في ميناء',
                "ميناء {$port->name} به {$port->active_trips_count} قوارب بانتظار/تحت الإحصاء حاليًا",
                'warning',
                now(),
            )));
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addEditedTripAlerts(Collection $alerts, User $user, array $portIds): void
    {
        Trip::query()->visibleTo($user)->with('port:id,name')->whereIn('port_id', $portIds)
            ->where('edited_after_approval', true)->where('approved_at', '>=', now()->subDays(config('alerts.edited_window_days')))
            ->get()->each(fn (Trip $trip) => $alerts->push($this->alert(
                'تعديل بيانات بعد الاعتماد',
                "الرحلة {$trip->trip_code} بميناء {$trip->port->name} تم تعديل بياناتها بعد الاعتماد",
                'warning',
                $trip->approved_at,
            )));
    }

    /** @param Collection<int, array{type: string, message: string, severity: string, time: mixed}> $alerts */
    private function addContractAlerts(Collection $alerts): void
    {
        Employee::query()->with('user:id,full_name')->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [today(), today()->addDays(config('alerts.contract_expiry_days'))])->get()
            ->each(fn (Employee $employee) => $alerts->push($this->alert(
                'عقد قرب من الانتهاء',
                "عقد الموظف {$employee->user->full_name} ينتهي بتاريخ {$employee->contract_end_date->format('Y-m-d')}",
                'warning',
                $employee->contract_end_date,
            )));
    }

    /** @return array{type: string, message: string, severity: string, time: mixed} */
    private function alert(string $type, string $message, string $severity, mixed $time): array
    {
        return compact('type', 'message', 'severity', 'time');
    }
}
