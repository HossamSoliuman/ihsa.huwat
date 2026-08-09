<?php

namespace App\Actions;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClosePayrollRunAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollRun $run, User $actor, ?string $ipAddress = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $ipAddress): PayrollRun {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($run->status !== PayrollRun::STATUS_PAID) {
                throw ValidationException::withMessages(['run' => 'لا يمكن إغلاق المسير قبل تسجيل صرفه.']);
            }

            $run->forceFill(['status' => PayrollRun::STATUS_CLOSED, 'closed_at' => now()])->save();
            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_closed',
                $run,
                ['status' => PayrollRun::STATUS_PAID],
                ['status' => PayrollRun::STATUS_CLOSED, 'closed_at' => $run->closed_at],
                ipAddress: $ipAddress,
            );

            return $run;
        });
    }
}
