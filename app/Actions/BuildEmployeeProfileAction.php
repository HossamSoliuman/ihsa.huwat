<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Bank;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeLoan;
use App\Models\EmployeeSalaryComponent;
use App\Models\JobTitle;
use App\Models\Nationality;
use App\Models\PayrollAdjustment;
use App\Models\Port;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class BuildEmployeeProfileAction
{
    public function execute(Employee $employee, User $user): array
    {
        Gate::forUser($user)->authorize('view', $employee);
        $canViewSalary = Gate::forUser($user)->allows('viewSalary', $employee);

        $employee = Employee::query()
            ->with([
                'user:id,full_name,username,email,is_active',
                'department:id,name',
                'jobTitle:id,name',
                'manager.user:id,full_name',
                'port:id,name',
                'bank:id,name',
                'contracts' => fn ($query) => $query->latest('start_date')->latest('id'),
                'salaryComponents' => fn ($query) => $query->with('salaryComponent')->latest('effective_from')->latest('id'),
                'loans' => fn ($query) => $query->with(['approver:id,full_name', 'instalments'])->latest('id'),
                'payrollAdjustments' => fn ($query) => $query->with(['salaryComponent:id,name_ar', 'creator:id,full_name', 'approver:id,full_name'])->latest('period_year')->latest('period_month')->latest('id'),
                'payrollRunEmployees' => fn ($query) => $query->with('payrollRun')->latest('id')->limit(24),
                'documents' => fn ($query) => $query->with('uploader:id,full_name')->latest('id'),
            ])
            ->findOrFail($employee->id);

        $salaryHistory = $canViewSalary ? $employee->salaryComponents : collect();
        $currentSalaryComponents = $salaryHistory
            ->filter(fn (EmployeeSalaryComponent $row): bool => $row->effective_from->lte(today()) && ($row->effective_to === null || $row->effective_to->gte(today())))
            ->groupBy('salary_component_id')
            ->map(fn (Collection $rows): EmployeeSalaryComponent => $rows->sortByDesc('effective_from')->first())
            ->sortBy(fn (EmployeeSalaryComponent $row): int => $row->salaryComponent->sort_order);

        return [
            'employee' => $employee,
            'canViewSalary' => $canViewSalary,
            'canManageSalary' => Gate::forUser($user)->allows('updateSalary', $employee),
            'canManageFinance' => Gate::forUser($user)->allows('create', PayrollAdjustment::class),
            'canApproveLoans' => in_array($user->role->code, ['super_admin', 'finance_officer'], true),
            'canApproveAdjustments' => in_array($user->role->code, ['super_admin', 'finance_officer'], true),
            'currentSalaryComponents' => $currentSalaryComponents,
            'currentBasicSalary' => $currentSalaryComponents->first(fn (EmployeeSalaryComponent $row): bool => $row->salaryComponent->is_basic)?->amount,
            'salaryHistory' => $salaryHistory,
            'salaryCatalog' => $canViewSalary ? SalaryComponent::query()->where('is_active', true)->ordered()->get() : collect(),
            'banks' => $canViewSalary ? Bank::query()->where('is_active', true)->ordered()->get() : collect(),
            'departments' => Department::query()->where('is_active', true)->ordered()->get(),
            'jobTitles' => JobTitle::query()->where('is_active', true)->ordered()->get(),
            'ports' => Port::query()->selectable()->orderBy('name')->get(['id', 'name']),
            'managers' => Employee::query()->with('user:id,full_name')->whereKeyNot($employee->id)->whereIn('status', ['active', 'on_leave'])->orderBy('employee_number')->get(),
            'audits' => $this->audits($employee),
            'nationalityLabel' => Nationality::labels()[$employee->nationality] ?? $employee->nationality,
        ];
    }

    private function audits(Employee $employee): Collection
    {
        return AuditLog::query()
            ->with('user:id,full_name')
            ->where(function (Builder $query) use ($employee): void {
                $query->where(fn (Builder $query) => $query->where('model_type', Employee::class)->where('model_id', $employee->id))
                    ->orWhere(fn (Builder $query) => $query->where('model_type', EmployeeSalaryComponent::class)->whereIn('model_id', $employee->salaryComponents->pluck('id')))
                    ->orWhere(fn (Builder $query) => $query->where('model_type', EmployeeLoan::class)->whereIn('model_id', $employee->loans->pluck('id')))
                    ->orWhere(fn (Builder $query) => $query->where('model_type', PayrollAdjustment::class)->whereIn('model_id', $employee->payrollAdjustments->pluck('id')))
                    ->orWhere(fn (Builder $query) => $query->where('model_type', EmployeeDocument::class)->whereIn('model_id', $employee->documents->pluck('id')));
            })
            ->latest('id')
            ->limit(100)
            ->get();
    }
}
