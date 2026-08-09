<?php

namespace App\Actions;

use App\Models\EmployeeLoan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveEmployeeLoanAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(EmployeeLoan $loan, User $actor, ?string $ipAddress = null): EmployeeLoan
    {
        return DB::transaction(function () use ($loan, $actor, $ipAddress): EmployeeLoan {
            $loan = EmployeeLoan::query()->lockForUpdate()->findOrFail($loan->id);

            if ($loan->status !== 'requested') {
                throw ValidationException::withMessages(['loan' => 'لا يمكن اعتماد السلفة في حالتها الحالية.']);
            }

            $amountCents = (int) round((float) $loan->amount * 100);
            $regularCents = intdiv($amountCents, $loan->instalments_count);
            $firstMonth = CarbonImmutable::parse($loan->first_instalment_month)->startOfMonth();

            for ($number = 1; $number <= $loan->instalments_count; $number++) {
                $dueMonth = $firstMonth->addMonths($number - 1);
                $instalmentCents = $number === $loan->instalments_count
                    ? $amountCents - ($regularCents * ($loan->instalments_count - 1))
                    : $regularCents;

                $loan->instalments()->create([
                    'instalment_number' => $number,
                    'due_year' => $dueMonth->year,
                    'due_month' => $dueMonth->month,
                    'amount' => $instalmentCents / 100,
                    'paid_amount' => 0,
                    'status' => 'scheduled',
                ]);
            }

            $loan->forceFill(['status' => 'approved', 'approved_by' => $actor->id, 'approved_at' => now()])->save();

            $this->recordAuditLog->execute(
                $actor,
                'employee_loan_approved',
                $loan,
                ['status' => 'requested'],
                ['status' => 'approved', 'approved_by' => $actor->id, 'instalments_count' => $loan->instalments_count],
                $loan->reason,
                $ipAddress,
            );

            return $loan->load('instalments');
        });
    }
}
