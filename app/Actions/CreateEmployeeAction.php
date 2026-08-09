<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployeeAction
{
    public function __construct(
        public CreateEmployeeContractAction $createEmployeeContract,
        public RecordAuditLogAction $recordAuditLog,
    ) {}

    public function execute(array $attributes, User $actor, ?string $ipAddress = null): Employee
    {
        return DB::transaction(function () use ($attributes, $actor, $ipAddress): Employee {
            Employee::query()->lockForUpdate()->get(['id']);

            $user = User::query()->create([
                'role_id' => $attributes['role_id'],
                'full_name' => $attributes['full_name'],
                'username' => $attributes['username'],
                'email' => $attributes['email'],
                'password_hash' => Hash::make($attributes['password']),
                'port_id' => $attributes['port_id'] ?? null,
                'is_active' => true,
            ]);

            $employee = $user->employee()->create([
                ...Arr::only($attributes, [
                    'national_id',
                    'nationality',
                    'date_of_birth',
                    'gender',
                    'phone',
                    'email',
                    'department_id',
                    'job_title_id',
                    'manager_id',
                    'port_id',
                    'hire_date',
                ]),
                'employee_number' => $this->nextEmployeeNumber(),
                'status' => 'active',
            ]);

            $this->createEmployeeContract->execute($employee, [
                'contract_type' => $attributes['contract_type'],
                'start_date' => $attributes['contract_start_date'],
                'end_date' => $attributes['contract_end_date'] ?? null,
                'probation_end_date' => $attributes['probation_end_date'] ?? null,
                'working_hours_per_day' => $attributes['working_hours_per_day'],
                'working_days_per_week' => $attributes['working_days_per_week'],
                'status' => 'active',
            ], $actor, $ipAddress);

            $basicSalaryComponent = SalaryComponent::query()->where('code', 'basic')->firstOrFail();
            $salary = EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->id,
                'salary_component_id' => $basicSalaryComponent->id,
                'amount' => $attributes['base_salary'],
                'effective_from' => $attributes['hire_date'],
                'created_by' => $actor->id,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'employee_created',
                $employee,
                newValues: $employee->only(['employee_number', 'department_id', 'job_title_id', 'hire_date', 'status']),
                ipAddress: $ipAddress,
            );
            $this->recordAuditLog->execute(
                $actor,
                'salary_component_created',
                $salary,
                newValues: $salary->only(['salary_component_id', 'amount', 'effective_from']),
                ipAddress: $ipAddress,
            );

            return $employee->load(['user', 'activeContract', 'salaryComponents.salaryComponent']);
        });
    }

    private function nextEmployeeNumber(): string
    {
        $prefix = (string) config('employment.employee_number_prefix', 'HWT');
        $lastNumber = Employee::query()
            ->where('employee_number', 'like', $prefix.'-%')
            ->orderByDesc('employee_number')
            ->value('employee_number');
        $sequence = $lastNumber === null
            ? 1
            : ((int) substr($lastNumber, strlen($prefix) + 1)) + 1;

        do {
            $number = $prefix.'-'.str_pad((string) $sequence++, 5, '0', STR_PAD_LEFT);
        } while (Employee::query()->where('employee_number', $number)->exists());

        return $number;
    }
}
