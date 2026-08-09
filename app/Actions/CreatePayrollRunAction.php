<?php

namespace App\Actions;

use App\Models\PayrollRun;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePayrollRunAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(int $year, int $month, User $actor, ?string $note = null, ?string $ipAddress = null): PayrollRun
    {
        return DB::transaction(function () use ($year, $month, $actor, $note, $ipAddress): PayrollRun {
            PayrollRun::query()->lockForUpdate()->get(['id']);

            if (PayrollRun::query()->where('period_year', $year)->where('period_month', $month)->exists()) {
                throw ValidationException::withMessages(['period' => 'يوجد مسير رواتب لهذه الفترة بالفعل.']);
            }

            $periodStart = CarbonImmutable::create($year, $month, 1)->startOfMonth();
            $prefix = (string) config('payroll.run_number_prefix', 'PR');
            $run = PayrollRun::query()->create([
                'run_number' => $prefix.'-'.$periodStart->format('Y-m'),
                'period_year' => $year,
                'period_month' => $month,
                'period_start' => $periodStart,
                'period_end' => $periodStart->endOfMonth(),
                'status' => PayrollRun::STATUS_DRAFT,
                'note' => $note,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_created',
                $run,
                newValues: $run->only(['run_number', 'period_year', 'period_month', 'period_start', 'period_end', 'status']),
                reason: $note,
                ipAddress: $ipAddress,
            );

            return $run;
        });
    }
}
