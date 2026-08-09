<?php

namespace App\Actions;

use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Nationality;
use App\Models\Port;
use Illuminate\Database\Eloquent\Builder;

class BuildEmployeeDirectoryAction
{
    public function execute(array $filters): array
    {
        return [
            'employees' => $this->query($filters)->paginate(20)->withQueryString(),
            'departments' => Department::query()->ordered()->get(['id', 'name']),
            'jobTitles' => JobTitle::query()->ordered()->get(['id', 'name']),
            'ports' => Port::query()->selectable()->orderBy('name')->get(['id', 'name']),
            'nationalities' => Nationality::labels(),
            'filters' => $filters,
        ];
    }

    public function query(array $filters): Builder
    {
        return Employee::query()
            ->select([
                'id',
                'user_id',
                'employee_number',
                'national_id',
                'nationality',
                'department_id',
                'job_title_id',
                'port_id',
                'hire_date',
                'status',
            ])
            ->with([
                'user:id,full_name',
                'department:id,name',
                'jobTitle:id,name',
                'port:id,name',
                'activeContract',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('employee_number', 'like', "%{$search}%")
                        ->orWhere('national_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn (Builder $query) => $query->where('full_name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['department_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('department_id', $id))
            ->when($filters['job_title_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('job_title_id', $id))
            ->when($filters['port_id'] ?? null, fn (Builder $query, int|string $id) => $query->where('port_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['nationality'] ?? null, fn (Builder $query, string $nationality) => $query->where('nationality', $nationality))
            ->when($filters['contract_type'] ?? null, fn (Builder $query, string $type) => $query->whereHas(
                'activeContract',
                fn (Builder $query) => $query->where('contract_type', $type),
            ))
            ->orderBy('employee_number');
    }
}
