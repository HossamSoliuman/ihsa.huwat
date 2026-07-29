<?php

namespace App\Actions;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GeneratePayrollAction
{
    public function execute(int $month, int $year): int
    {
        $periodStart = CarbonImmutable::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->endOfMonth()->endOfDay();

        return DB::transaction(function () use ($month, $year, $periodStart, $periodEnd): int {
            $created = 0;
            $employees = Employee::query()->with([
                'attendance' => fn ($query) => $query->with('shift:id,start_time,end_time')
                    ->whereBetween('attendance_date', [$periodStart, $periodEnd]),
            ])->where('status', '<>', 'terminated')->lockForUpdate()->get();

            foreach ($employees as $employee) {
                $baseSalary = (float) $employee->base_salary;
                $overtimeHours = round($employee->attendance->sum(fn (Attendance $attendance): float => $this->overtimeHours($attendance)), 2);
                $overtimeAmount = round($overtimeHours * ($baseSalary / 240) * 1.5, 2);
                $absentDays = $employee->attendance->where('status', 'absent')->count();
                $deductions = round($absentDays * ($baseSalary / 30), 2);

                $payroll = Payroll::query()->firstOrCreate(
                    ['employee_id' => $employee->id, 'period_month' => $month, 'period_year' => $year],
                    [
                        'base_salary' => $baseSalary,
                        'overtime_hours' => $overtimeHours,
                        'overtime_amount' => $overtimeAmount,
                        'deductions' => $deductions,
                        'net_salary' => round($baseSalary + $overtimeAmount - $deductions, 2),
                    ],
                );

                if ($payroll->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        });
    }

    private function overtimeHours(Attendance $attendance): float
    {
        if ($attendance->check_in === null || $attendance->check_out === null) {
            return 0;
        }

        $shiftStart = CarbonImmutable::parse($attendance->attendance_date->toDateString().' '.$attendance->shift->start_time);
        $shiftEnd = CarbonImmutable::parse($attendance->attendance_date->toDateString().' '.$attendance->shift->end_time);

        if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
            $shiftEnd = $shiftEnd->addDay();
        }

        $scheduledMinutes = $shiftStart->diffInMinutes($shiftEnd);
        $workedMinutes = $attendance->check_in->diffInMinutes($attendance->check_out);

        return max(0, $workedMinutes - $scheduledMinutes) / 60;
    }
}
