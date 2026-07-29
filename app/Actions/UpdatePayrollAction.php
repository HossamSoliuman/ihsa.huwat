<?php

namespace App\Actions;

use App\Models\Payroll;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdatePayrollAction
{
    public function execute(Payroll $payroll, array $attributes): Payroll
    {
        return DB::transaction(function () use ($payroll, $attributes): Payroll {
            $payroll = Payroll::query()->lockForUpdate()->findOrFail($payroll->id);
            $this->ensurePending($payroll);
            $netSalary = (float) $payroll->base_salary + (float) $attributes['allowances']
                + (float) $payroll->overtime_amount + (float) $attributes['bonuses'] - (float) $attributes['deductions'];
            $payroll->update([...$attributes, 'net_salary' => round($netSalary, 2)]);

            return $payroll;
        });
    }

    private function ensurePending(Payroll $payroll): void
    {
        if ($payroll->paid_status === 'paid') {
            throw ValidationException::withMessages(['payroll' => 'لا يمكن تعديل راتب تم صرفه.']);
        }
    }
}
