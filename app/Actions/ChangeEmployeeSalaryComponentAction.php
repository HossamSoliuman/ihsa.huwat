<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeEmployeeSalaryComponentAction
{
    public function __construct(public RecordAuditLogAction $recordAuditLog) {}

    public function execute(
        Employee $employee,
        SalaryComponent $salaryComponent,
        array $attributes,
        User $actor,
        ?string $ipAddress = null,
    ): EmployeeSalaryComponent {
        return DB::transaction(function () use ($employee, $salaryComponent, $attributes, $actor, $ipAddress): EmployeeSalaryComponent {
            Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $salaryRows = EmployeeSalaryComponent::query()
                ->where('employee_id', $employee->id)
                ->where('salary_component_id', $salaryComponent->id)
                ->lockForUpdate()
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->get();
            $latestSalary = $salaryRows->first();
            $effectiveFrom = CarbonImmutable::parse($attributes['effective_from'])->startOfDay();

            if ($latestSalary !== null && $effectiveFrom->lessThanOrEqualTo($latestSalary->effective_from)) {
                throw ValidationException::withMessages([
                    'effective_from' => 'يجب أن يبدأ التغيير بعد آخر سجل لهذا المكوّن.',
                ]);
            }

            if ($latestSalary !== null) {
                $latestSalary->forceFill(['effective_to' => $effectiveFrom->subDay()->toDateString()])->save();
            }

            $salary = EmployeeSalaryComponent::query()->create([
                'employee_id' => $employee->id,
                'salary_component_id' => $salaryComponent->id,
                'amount' => $salaryComponent->calculation_type === SalaryComponent::CALCULATION_FIXED
                    ? $attributes['amount']
                    : null,
                'percentage' => $salaryComponent->calculation_type === SalaryComponent::CALCULATION_PERCENT_OF_BASIC
                    ? $attributes['percentage']
                    : null,
                'effective_from' => $effectiveFrom,
                'created_by' => $actor->id,
            ]);

            $this->recordAuditLog->execute(
                $actor,
                'salary_component_changed',
                $salary,
                $latestSalary?->only(['amount', 'percentage', 'effective_from', 'effective_to']),
                $salary->only(['amount', 'percentage', 'effective_from', 'effective_to']),
                $attributes['reason'] ?? null,
                $ipAddress,
            );

            return $salary;
        });
    }
}
