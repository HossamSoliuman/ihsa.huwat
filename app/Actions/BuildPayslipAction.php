<?php

namespace App\Actions;

use App\Models\PayrollRunEmployee;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class BuildPayslipAction
{
    public function execute(PayrollRunEmployee $snapshot, User $user): array
    {
        $snapshot->load([
            'payrollRun:id,run_number,period_year,period_month,period_start,period_end,payment_date,status,payment_reference',
            'employee:id,user_id,bank_id,iban,account_holder_name',
            'employee.bank:id,name',
            'items' => fn ($query) => $query->orderBy('item_type')->orderBy('id'),
        ]);

        Gate::forUser($user)->authorize('viewPayslip', $snapshot->employee);

        if ($user->role->code === 'employee_portal' && ! in_array($snapshot->payrollRun->status, ['approved', 'paid', 'closed'], true)) {
            throw new AuthorizationException;
        }

        return [
            'snapshot' => $snapshot,
            'earnings' => $snapshot->items->where('item_type', 'earning'),
            'deductions' => $snapshot->items->where('item_type', 'deduction'),
        ];
    }
}
