<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewEmployeeContractAction
{
    public function __construct(
        public CreateEmployeeContractAction $createEmployeeContract,
        public RecordAuditLogAction $recordAuditLog,
    ) {}

    public function execute(Employee $employee, array $attributes, User $actor, ?string $ipAddress = null): EmployeeContract
    {
        return DB::transaction(function () use ($employee, $attributes, $actor, $ipAddress): EmployeeContract {
            $activeContract = EmployeeContract::query()
                ->where('employee_id', $employee->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if ($activeContract === null) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يوجد عقد نشط يمكن تجديده.',
                ]);
            }

            $startDate = CarbonImmutable::parse($attributes['start_date']);

            if ($startDate->lessThanOrEqualTo($activeContract->start_date)) {
                throw ValidationException::withMessages([
                    'start_date' => 'يجب أن يبدأ العقد الجديد بعد بداية العقد الحالي.',
                ]);
            }

            $oldContractValues = $activeContract->only(['status', 'end_date']);
            $activeContract->forceFill([
                'end_date' => $startDate->subDay()->toDateString(),
                'status' => 'expired',
            ])->save();

            $this->recordAuditLog->execute(
                $actor,
                'employee_contract_expired_on_renewal',
                $activeContract,
                $oldContractValues,
                $activeContract->only(['status', 'end_date']),
                $attributes['note'] ?? null,
                $ipAddress,
            );

            return $this->createEmployeeContract->execute($employee, [
                ...$attributes,
                'status' => 'active',
            ], $actor, $ipAddress);
        });
    }
}
