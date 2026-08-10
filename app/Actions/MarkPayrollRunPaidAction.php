<?php

namespace App\Actions;

use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkPayrollRunPaidAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollRun $run, array $attributes, User $actor, ?string $ipAddress = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $attributes, $actor, $ipAddress): PayrollRun {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($run->status !== PayrollRun::STATUS_APPROVED) {
                throw ValidationException::withMessages(['run' => 'لا يمكن تسجيل الصرف قبل اعتماد المسير.']);
            }

            $run->forceFill([
                'status' => PayrollRun::STATUS_PAID,
                'payment_date' => $attributes['payment_date'],
                'payment_reference' => $attributes['payment_reference'],
                'note' => $attributes['note'] ?? $run->note,
                'paid_at' => now(),
            ])->save();

            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_paid',
                $run,
                ['status' => PayrollRun::STATUS_APPROVED],
                $run->only(['status', 'payment_date', 'payment_reference', 'paid_at']),
                $attributes['note'] ?? null,
                $ipAddress,
            );

            return $run;
        });
    }
}
