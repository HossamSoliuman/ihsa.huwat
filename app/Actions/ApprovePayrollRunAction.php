<?php

namespace App\Actions;

use App\Models\EmployeeLoan;
use App\Models\LoanInstalment;
use App\Models\PayrollAdjustment;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovePayrollRunAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(PayrollRun $run, User $actor, ?string $ipAddress = null): PayrollRun
    {
        return DB::transaction(function () use ($run, $actor, $ipAddress): PayrollRun {
            $run = PayrollRun::query()->lockForUpdate()->findOrFail($run->id);

            if ($run->status !== PayrollRun::STATUS_CALCULATED) {
                throw ValidationException::withMessages(['run' => 'يجب احتساب المسير قبل اعتماده.']);
            }

            if ($run->issues()->where('level', 'error')->where('resolved', false)->exists()) {
                throw ValidationException::withMessages(['issues' => 'يجب معالجة أخطاء المسير قبل الاعتماد.']);
            }

            $items = PayrollRunItem::query()
                ->whereIn('payroll_run_employee_id', $run->employees()->select('id'))
                ->whereNotNull('source_id')
                ->lockForUpdate()
                ->get(['source_type', 'source_id', 'amount']);
            $adjustmentIds = $items->where('source_type', PayrollAdjustment::class)->pluck('source_id');

            PayrollAdjustment::query()->whereIn('id', $adjustmentIds)
                ->where('status', PayrollAdjustment::STATUS_APPROVED)
                ->update(['status' => PayrollAdjustment::STATUS_CONSUMED, 'payroll_run_id' => $run->id, 'updated_at' => now()]);

            $loanItems = $items->where('source_type', LoanInstalment::class);

            foreach ($loanItems as $item) {
                LoanInstalment::query()->whereKey($item->source_id)->where('status', 'scheduled')->update([
                    'status' => 'deducted',
                    'paid_amount' => $item->amount,
                    'payroll_run_id' => $run->id,
                    'updated_at' => now(),
                ]);
            }

            $loanIds = LoanInstalment::query()->whereIn('id', $loanItems->pluck('source_id'))->pluck('loan_id')->unique();

            foreach (EmployeeLoan::query()->whereIn('id', $loanIds)->lockForUpdate()->get() as $loan) {
                $loan->forceFill([
                    'status' => $loan->instalments()->where('status', 'scheduled')->exists() ? 'active' : 'completed',
                ])->save();
            }

            $run->forceFill([
                'status' => PayrollRun::STATUS_APPROVED,
                'approved_by' => $actor->id,
                'approved_at' => now(),
            ])->save();

            $this->recordAuditLog->execute(
                $actor,
                'payroll_run_approved',
                $run,
                ['status' => PayrollRun::STATUS_CALCULATED],
                ['status' => PayrollRun::STATUS_APPROVED, 'approved_by' => $actor->id],
                ipAddress: $ipAddress,
            );

            return $run;
        });
    }
}
