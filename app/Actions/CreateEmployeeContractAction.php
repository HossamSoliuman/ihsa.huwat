<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEmployeeContractAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(Employee $employee, array $attributes, User $actor, ?string $ipAddress = null): EmployeeContract
    {
        return DB::transaction(function () use ($employee, $attributes, $actor, $ipAddress): EmployeeContract {
            $lockedEmployee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $contracts = EmployeeContract::query()
                ->where('employee_id', $lockedEmployee->id)
                ->lockForUpdate()
                ->get();

            $status = $attributes['status'] ?? 'active';

            if ($status === 'active' && $contracts->contains('status', 'active')) {
                throw ValidationException::withMessages([
                    'contract' => 'يوجد عقد نشط لهذا الموظف بالفعل.',
                ]);
            }

            EmployeeContract::query()->lockForUpdate()->get(['id']);

            $contract = $lockedEmployee->contracts()->create([
                ...$attributes,
                'contract_number' => $this->nextContractNumber(),
                'status' => $status,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'employee_contract_created',
                $contract,
                newValues: $contract->only(['employee_id', 'contract_number', 'contract_type', 'start_date', 'end_date', 'status']),
                reason: $attributes['note'] ?? null,
                ipAddress: $ipAddress,
            );

            return $contract;
        });
    }

    private function nextContractNumber(): string
    {
        $prefix = (string) config('employment.contract_number_prefix', 'HWT-C');
        $lastNumber = EmployeeContract::query()
            ->where('contract_number', 'like', $prefix.'-%')
            ->orderByDesc('contract_number')
            ->value('contract_number');
        $sequence = $lastNumber === null
            ? 1
            : ((int) substr($lastNumber, strlen($prefix) + 1)) + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
        } while (EmployeeContract::query()->where('contract_number', $number)->exists());

        return $number;
    }
}
