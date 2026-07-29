<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildPayrollDashboardAction;
use App\Actions\GeneratePayrollAction;
use App\Actions\PayPayrollAction;
use App\Actions\UpdatePayrollAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterPayrollRequest;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Requests\PayPayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use App\Models\Payroll;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PayrollController extends Controller
{
    public function index(FilterPayrollRequest $request, BuildPayrollDashboardAction $action): View
    {
        return view('dashboard.payroll.index', $action->execute($request->integer('month'), $request->integer('year')));
    }

    public function generate(GeneratePayrollRequest $request, GeneratePayrollAction $action): RedirectResponse
    {
        $created = $action->execute($request->integer('month'), $request->integer('year'));

        return to_route('dashboard.payroll.index', $request->safe()->only(['month', 'year']))
            ->with('status', "تم توليد {$created} سجل راتب جديد.");
    }

    public function update(UpdatePayrollRequest $request, Payroll $payroll, UpdatePayrollAction $action): RedirectResponse
    {
        $action->execute($payroll, $request->validated());

        return back()->with('status', 'تم تحديث مستحقات الراتب.');
    }

    public function pay(PayPayrollRequest $request, Payroll $payroll, PayPayrollAction $action): RedirectResponse
    {
        $action->execute($payroll);

        return back()->with('status', 'تم اعتماد صرف الراتب.');
    }
}
