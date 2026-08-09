<?php

namespace App\Actions;

use App\Models\PayrollRun;
use Illuminate\Database\Eloquent\Builder;

class BuildPayrollRunsAction
{
    public function execute(array $filters): array
    {
        $query = PayrollRun::query()
            ->with(['creator:id,full_name', 'approver:id,full_name'])
            ->withCount([
                'issues as errors_count' => fn (Builder $query) => $query->where('level', 'error')->where('resolved', false),
                'issues as warnings_count' => fn (Builder $query) => $query->where('level', 'warning')->where('resolved', false),
            ])
            ->when($filters['year'] ?? null, fn (Builder $query, int|string $year) => $query->where('period_year', $year))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->orderByDesc('period_year')
            ->orderByDesc('period_month');

        return [
            'runs' => $query->paginate(18)->withQueryString(),
            'years' => PayrollRun::query()->select('period_year')->distinct()->orderByDesc('period_year')->pluck('period_year'),
            'filters' => $filters,
            'summary' => PayrollRun::query()->toBase()->selectRaw(
                'COUNT(*) AS runs_count, COALESCE(SUM(employees_count), 0) AS employees_count, COALESCE(SUM(net_total), 0) AS net_total, COALESCE(SUM(CASE WHEN status IN (?, ?) THEN 1 ELSE 0 END), 0) AS sealed_count',
                [PayrollRun::STATUS_PAID, PayrollRun::STATUS_CLOSED],
            )->first(),
        ];
    }
}
