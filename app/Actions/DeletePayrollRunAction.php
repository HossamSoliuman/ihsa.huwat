<?php

namespace App\Actions;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePayrollRunAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollRun $run, User $actor, ?string $ipAddress = null): void
    {
        DB::transaction(function () use ($run, $actor, $ipAddress): void {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($run->status !== PayrollRun::STATUS_DRAFT) {
                throw ValidationException::withMessages(['run' => 'لا يمكن حذف المسير بعد احتسابه.']);
            }

            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_deleted',
                $run,
                oldValues: $run->only(['run_number', 'period_year', 'period_month', 'status']),
                ipAddress: $ipAddress,
            );
            $run->delete();
        });
    }
}
