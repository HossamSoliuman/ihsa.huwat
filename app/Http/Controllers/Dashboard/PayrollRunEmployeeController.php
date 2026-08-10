<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildPayslipAction;
use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\PayrollRunEmployee;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PayrollRunEmployeeController extends Controller
{
    public function show(
        Request $request,
        PayrollRun $payrollRun,
        PayrollRunEmployee $payrollRunEmployee,
        BuildPayslipAction $action,
    ): View {
        $this->authorize('view', $payrollRun);
        abort_unless($payrollRunEmployee->payroll_run_id === $payrollRun->id, 404);

        return view('dashboard.payroll.employee', $action->execute($payrollRunEmployee, $request->user()));
    }
}
