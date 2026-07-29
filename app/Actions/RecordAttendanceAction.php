<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\EmployeeAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordAttendanceAction
{
    public function checkIn(EmployeeAssignment $assignment): Attendance
    {
        return DB::transaction(function () use ($assignment): Attendance {
            $assignment = EmployeeAssignment::query()->with('shift')->lockForUpdate()->findOrFail($assignment->id);
            $lateAfter = Carbon::parse($assignment->assignment_date->toDateString().' '.$assignment->shift->start_time)->addMinutes(15);

            return Attendance::query()->updateOrCreate(
                ['employee_id' => $assignment->employee_id, 'attendance_date' => $assignment->assignment_date, 'shift_id' => $assignment->shift_id],
                ['check_in' => now(), 'check_out' => null, 'status' => now()->greaterThan($lateAfter) ? 'late' : 'present'],
            );
        });
    }

    public function checkOut(EmployeeAssignment $assignment): Attendance
    {
        return DB::transaction(function () use ($assignment): Attendance {
            $attendance = $this->findForUpdate($assignment);

            if ($attendance->check_in === null) {
                throw ValidationException::withMessages(['attendance' => 'يجب تسجيل الحضور قبل الانصراف.']);
            }

            $attendance->update(['check_out' => now()]);

            return $attendance;
        });
    }

    public function markAbsent(EmployeeAssignment $assignment): Attendance
    {
        return DB::transaction(fn (): Attendance => Attendance::query()->updateOrCreate(
            ['employee_id' => $assignment->employee_id, 'attendance_date' => $assignment->assignment_date, 'shift_id' => $assignment->shift_id],
            ['check_in' => null, 'check_out' => null, 'status' => 'absent'],
        ));
    }

    public function swapShift(EmployeeAssignment $assignment, int $shiftId): EmployeeAssignment
    {
        return DB::transaction(function () use ($assignment, $shiftId): EmployeeAssignment {
            $assignment = EmployeeAssignment::query()->lockForUpdate()->findOrFail($assignment->id);
            $hasAttendance = Attendance::query()->where('employee_id', $assignment->employee_id)
                ->whereDate('attendance_date', $assignment->assignment_date)->where('shift_id', $assignment->shift_id)->exists();

            if ($hasAttendance) {
                throw ValidationException::withMessages(['shift_id' => 'لا يمكن تبديل المناوبة بعد تسجيل حالة الحضور.']);
            }

            $assignment->update(['shift_id' => $shiftId]);

            return $assignment;
        });
    }

    private function findForUpdate(EmployeeAssignment $assignment): Attendance
    {
        $attendance = Attendance::query()->where('employee_id', $assignment->employee_id)
            ->whereDate('attendance_date', $assignment->assignment_date)->where('shift_id', $assignment->shift_id)
            ->lockForUpdate()->first();

        if ($attendance === null) {
            throw ValidationException::withMessages(['attendance' => 'لم يتم تسجيل حضور لهذه المناوبة.']);
        }

        return $attendance;
    }
}
