<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StartTripCountingAction
{
    public function execute(Trip $trip, User $user): Trip
    {
        return DB::transaction(function () use ($trip, $user): Trip {
            $trip = Trip::query()->lockForUpdate()->findOrFail($trip->id);
            $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();
            $assignedToday = $employee->assignments()->whereDate('assignment_date', today())->where('port_id', $trip->port_id)->exists();

            if (! $assignedToday || ! in_array($trip->status, ['arrived', 'waiting_employee'], true)
                || ($trip->assigned_employee_id !== null && $trip->assigned_employee_id !== $employee->id)) {
                throw ValidationException::withMessages(['trip' => 'لم تعد الرحلة متاحة لبدء الإحصاء.']);
            }

            $trip->update(['assigned_employee_id' => $employee->id, 'status' => 'counting', 'counting_started_at' => now()]);

            return $trip;
        });
    }
}
