<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeBankDetailsAction
{
    public function __construct(private RecordAuditLogAction $recordAuditLog) {}

    public function execute(Employee $employee, array $attributes, User $actor, ?string $ipAddress = null): Employee
    {
        return DB::transaction(function () use ($employee, $attributes, $actor, $ipAddress): Employee {
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $oldValues = $employee->only(['bank_id', 'iban', 'account_holder_name']);
            $employee->fill($attributes)->save();

            $this->recordAuditLog->execute(
                $actor,
                'employee_bank_details_updated',
                $employee,
                $oldValues,
                $employee->only(['bank_id', 'iban', 'account_holder_name']),
                ipAddress: $ipAddress,
            );

            return $employee;
        });
    }
}
