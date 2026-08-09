<?php

namespace App\Actions;

use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePayrollAdjustmentAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(array $attributes, User $actor, ?string $ipAddress = null): PayrollAdjustment
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress): PayrollAdjustment {
            $this->ensurePeriodOpen((int) $attributes['period_year'], (int) $attributes['period_month']);

            $adjustment = PayrollAdjustment::query()->create([
                ...$attributes,
                'status' => PayrollAdjustment::STATUS_DRAFT,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'payroll_adjustment_created',
                $adjustment,
                newValues: $adjustment->only(['employee_id', 'salary_component_id', 'adjustment_type', 'period_year', 'period_month', 'amount', 'reason', 'status']),
                reason: $adjustment->reason,
                ipAddress: $ipAddress,
            );

            return $adjustment;
        });
    }

    private function ensurePeriodOpen(int $year, int $month): void
    {
        if (PayrollRun::query()->where('period_year', $year)->where('period_month', $month)
            ->whereIn('status', [PayrollRun::STATUS_APPROVED, PayrollRun::STATUS_PAID, PayrollRun::STATUS_CLOSED])->exists()) {
            throw ValidationException::withMessages(['period' => 'فترة الرواتب معتمدة ولا تقبل استحقاقات أو خصومات جديدة.']);
        }
    }
}
