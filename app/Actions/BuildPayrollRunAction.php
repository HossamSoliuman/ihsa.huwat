<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\PayrollRun;

class BuildPayrollRunAction
{
    public function execute(PayrollRun $run): array
    {
        $run->load([
            'creator:id,full_name',
            'approver:id,full_name',
            'employees' => fn ($query) => $query->with(['employee:id,user_id', 'items.salaryComponent'])->orderBy('employee_number'),
            'issues' => fn ($query) => $query->with('employee.user:id,full_name')->orderBy('level')->orderBy('id'),
        ]);

        return [
            'run' => $run,
            'audits' => AuditLog::query()
                ->with('user:id,full_name')
                ->where('model_type', PayrollRun::class)
                ->where('model_id', $run->id)
                ->latest('id')
                ->get(),
        ];
    }
}
