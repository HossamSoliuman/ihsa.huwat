<?php

namespace App\Actions;

use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovePayrollAdjustmentAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollAdjustment $adjustment, User $actor, ?string $ipAddress = null): PayrollAdjustment
    {
        return DB::transaction(function () use ($adjustment, $actor, $ipAddress): PayrollAdjustment {
            $adjustment = PayrollAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);

            if ($adjustment->status !== PayrollAdjustment::STATUS_DRAFT) {
                throw ValidationException::withMessages(['adjustment' => 'لا يمكن اعتماد هذا السجل في حالته الحالية.']);
            }

            if (PayrollRun::query()->where('period_year', $adjustment->period_year)->where('period_month', $adjustment->period_month)
                ->whereIn('status', [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_PAID, PayrollRun::STATUS_CLOSED])->exists()) {
                throw ValidationException::withMessages(['period' => 'فترة الرواتب معتمدة ولا تقبل تغييرات جديدة.']);
            }

            $adjustment->forceFill(['status' => PayrollAdjustment::STATUS_APPROVED, 'approved_by' => $actor->id])->save();

            $this->recordAuditLog->execute(
                $actor,
                'payroll_adjustment_approved',
                $adjustment,
                ['status' => PayrollAdjustment::STATUS_DRAFT],
                ['status' => PayrollAdjustment::STATUS_APPROVED, 'approved_by' => $actor->id],
                $adjustment->reason,
                $ipAddress,
            );

            return $adjustment;
        });
    }
}
