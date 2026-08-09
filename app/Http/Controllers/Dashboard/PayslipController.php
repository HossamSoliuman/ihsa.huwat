<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildPayslipAction;
use App\Http\Controllers\Controller;
use App\Models\PayrollRunEmployee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PayslipController extends Controller
{
    public function __invoke(Request $request, PayrollRunEmployee $payrollRunEmployee, BuildPayslipAction $action): View
    {
        return view('dashboard.payroll.payslip', $action->execute($payrollRunEmployee, $request->user()));
    }
}
