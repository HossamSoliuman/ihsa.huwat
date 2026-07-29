<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Port;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagePortOperationsAction
{
    public function assignTrip(Port $port, Trip $trip, int $employeeId): void
    {
        DB::transaction(function () use ($port, $trip, $employeeId): void {
            $lockedTrip = Trip::query()->lockForUpdate()->whereKey($trip->id)->where('port_id', $port->id)->firstOrFail();
            if (! in_array($lockedTrip->status, ['arrived', 'waiting_employee', 'counting'], true)) {
                throw ValidationException::withMessages(['trip' => 'لم تعد الرحلة متاحة للإسناد.']);
            }

            $this->ensureEmployeeAvailable($port, $employeeId, $lockedTrip->assigned_employee_id);
            $attributes = ['assigned_employee_id' => $employeeId];
            if ($lockedTrip->status !== 'counting') {
                $attributes += ['status' => 'counting', 'counting_started_at' => now()];
            }
            $lockedTrip->update($attributes);
        });
    }

    public function transferToReview(Port $port, Trip $trip): void
    {
        DB::transaction(function () use ($port, $trip): void {
            $lockedTrip = Trip::query()->lockForUpdate()->whereKey($trip->id)->where('port_id', $port->id)->firstOrFail();
            if (! in_array($lockedTrip->status, ['arrived', 'waiting_employee', 'counting'], true)) {
                throw ValidationException::withMessages(['trip' => 'لا يمكن تحويل الرحلة في حالتها الحالية.']);
            }
            if (! $lockedTrip->discrepancies()->where('review_status', '!=', 'approved')->exists()) {
                throw ValidationException::withMessages(['trip' => 'لا يوجد فرق معلّق يتطلب المراجعة.']);
            }
            $lockedTrip->update(['status' => 'pending_review']);
        });
    }

    public function approveDiscrepancy(User $reviewer, Port $port, TripDiscrepancy $discrepancy): void
    {
        DB::transaction(function () use ($reviewer, $port, $discrepancy): void {
            $locked = TripDiscrepancy::query()->lockForUpdate()->whereKey($discrepancy->id)->firstOrFail();
            $trip = Trip::query()->lockForUpdate()->whereKey($locked->trip_id)->where('port_id', $port->id)->firstOrFail();
            if ($locked->review_status === 'approved' || $trip->status !== 'pending_review') {
                throw ValidationException::withMessages(['discrepancy' => 'تمت معالجة هذا الفرق مسبقاً.']);
            }
            $locked->update(['review_status' => 'approved', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
            $trip->update(['status' => 'approved', 'approved_by' => $reviewer->id, 'approved_at' => now()]);
        });
    }

    public function addAssignment(Port $port, array $data): void
    {
        DB::transaction(function () use ($port, $data): void {
            Employee::query()->lockForUpdate()->whereKey($data['employee_id'])->where('status', 'active')->firstOrFail();
            if (EmployeeAssignment::query()->where('employee_id', $data['employee_id'])->whereDate('assignment_date', today())->exists()) {
                throw ValidationException::withMessages(['employee_id' => 'الموظف مسند بالفعل إلى مناوبة اليوم.']);
            }
            EmployeeAssignment::query()->create([
                'employee_id' => $data['employee_id'], 'port_id' => $port->id, 'shift_id' => $data['shift_id'],
                'assignment_date' => today(), 'is_temporary' => (bool) ($data['is_temporary'] ?? false),
            ]);
        });
    }

    private function ensureEmployeeAvailable(Port $port, int $employeeId, ?int $currentEmployeeId): void
    {
        if ($currentEmployeeId === $employeeId) {
            return;
        }
        $assignment = EmployeeAssignment::query()->where('employee_id', $employeeId)->where('port_id', $port->id)
            ->whereDate('assignment_date', today())->first();
        if (! $assignment) {
            throw ValidationException::withMessages(['employee_id' => 'الموظف غير مسند إلى هذا الميناء اليوم.']);
        }
        $present = $assignment->employee->attendance()->whereDate('attendance_date', today())->where('shift_id', $assignment->shift_id)->whereIn('status', ['present', 'late'])->exists();
        $busy = $assignment->employee->assignedTrips()->whereIn('status', ['waiting_employee', 'counting'])->exists();
        if (! $present || $busy) {
            throw ValidationException::withMessages(['employee_id' => 'الموظف غير متاح حالياً.']);
        }
    }
}
