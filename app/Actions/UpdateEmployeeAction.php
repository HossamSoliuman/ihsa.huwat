<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateEmployeeAction
{
    public function __construct(private RecordAuditLogAction $recordAuditLog) {}

    public function execute(Employee $employee, array $attributes, User $actor, ?string $ipAddress = null): Employee
    {
        return DB::transaction(function () use ($employee, $attributes, $actor, $ipAddress): Employee {
            $employee = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $user = User::query()->lockForUpdate()->findOrFail($employee->user_id);
            $oldValues = [
                ...$employee->only(['national_id', 'nationality', 'date_of_birth', 'gender', 'phone', 'email', 'department_id', 'job_title_id', 'manager_id', 'port_id', 'status', 'termination_date', 'termination_reason']),
                'full_name' => $user->full_name,
            ];

            $user->forceFill(['full_name' => $attributes['full_name'], 'email' => $attributes['email']])->save();
            $employee->fill(Arr::except($attributes, 'full_name'));

            if ($attributes['status'] !== 'terminated') {
                $employee->termination_date = null;
                $employee->termination_reason = null;
            }

            $employee->save();

            $this->recordAuditLog->execute(
                $actor,
                $attributes['status'] === 'terminated' ? 'employee_terminated' : 'employee_updated',
                $employee,
                $oldValues,
                [...$employee->only(array_keys(Arr::except($oldValues, 'full_name'))), 'full_name' => $user->full_name],
                $attributes['termination_reason'] ?? null,
                $ipAddress,
            );

            return $employee->load('user');
        });
    }
}
