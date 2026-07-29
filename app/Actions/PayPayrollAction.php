<?php

namespace App\Actions;

use App\Models\Payroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayPayrollAction
{
    public function execute(Payroll $payroll): Payroll
    {
        return DB::transaction(function () use ($payroll): Payroll {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);

            if ($payroll->paid_status === 'paid') {
                throw ValidationException::withMessages(['payroll' => 'تم صرف هذا الراتب بالفعل.']);
            }

            $payroll->update(['paid_status' => 'paid', 'paid_at' => now()]);

            return $payroll;
        });
    }
}
