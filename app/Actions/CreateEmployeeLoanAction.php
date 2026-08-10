<?php

namespace App\Actions;

use App\Models\EmployeeLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateEmployeeLoanAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(array $attributes, User $actor, ?string $ipAddress = null): EmployeeLoan
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress): EmployeeLoan {
            EmployeeLoan::query()->lockForUpdate()->get(['id']);
            $amount = round((float) $attributes['amount'], 2);
            $instalmentsCount = (int) $attributes['instalments_count'];
            $loan = EmployeeLoan::query()->create([
                ...$attributes,
                'loan_number' => $this->nextLoanNumber(),
                'amount' => $amount,
                'instalment_amount' => round($amount / $instalmentsCount, 2),
                'first_instalment_month' => CarbonImmutable::parse($attributes['first_instalment_month'])->startOfMonth(),
                'status' => 'requested',
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'employee_loan_created',
                $loan,
                newValues: $loan->only(['employee_id', 'loan_number', 'amount', 'instalments_count', 'instalment_amount', 'first_instalment_month', 'status']),
                reason: $loan->reason,
                ipAddress: $ipAddress,
            );

            return $loan;
        });
    }

    private function nextLoanNumber(): string
    {
        $prefix = (string) config('payroll.loan_number_prefix', 'LN');
        $lastNumber = EmployeeLoan::query()->where('loan_number', 'like', $prefix.'-%')->orderByDesc('loan_number')->value('loan_number');
        $sequence = $lastNumber === null ? 1 : ((int) str($lastNumber)->afterLast('-')->toString()) + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
        } while (EmployeeLoan::query()->where('loan_number', $number)->exists());

        return $number;
    }
}
