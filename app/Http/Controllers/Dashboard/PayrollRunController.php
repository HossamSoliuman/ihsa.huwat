<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ApprovePayrollRunAction;
use App\Actions\BuildPayrollRunAction;
use App\Actions\BuildPayrollRunsAction;
use App\Actions\CalculatePayrollRunAction;
use App\Actions\ClosePayrollRunAction;
use App\Actions\CreatePayrollRunAction;
use App\Actions\DeletePayrollRunAction;
use App\Actions\MarkPayrollRunPaidAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\MarkPayrollRunPaidRequest;
use App\Http\Requests\StorePayrollRunRequest;
use App\Models\PayrollRun;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollRunController extends Controller
{
    public function index(Request $request, BuildPayrollRunsAction $action): View
    {
        $this->authorize('viewAny', PayrollRun::class);
        $filters = $request->validate([
            'year' => ['nullable', 'integer', 'between:2020,2100'],
            'status' => ['nullable', Rule::in(array_keys(config('payroll.run_statuses')))],
        ]);

        return view('dashboard.payroll.index', $action->execute($filters));
    }

    public function store(StorePayrollRunRequest $request, CreatePayrollRunAction $action): RedirectResponse
    {
        $run = $action->execute(
            $request->integer('period_year'),
            $request->integer('period_month'),
            $request->user(),
            $request->string('note')->toString() ?: null,
            $request->ip(),
        );

        return to_route('dashboard.payroll.runs.show', $run)->with('status', 'تم إنشاء مسير الرواتب كمسودة.');
    }

    public function show(PayrollRun $payrollRun, BuildPayrollRunAction $action): View
    {
        $this->authorize('view', $payrollRun);

        return view('dashboard.payroll.show', $action->execute($payrollRun));
    }

    public function calculate(Request $request, PayrollRun $payrollRun, CalculatePayrollRunAction $action): RedirectResponse
    {
        $this->authorize('calculate', $payrollRun);
        $action->execute($payrollRun, $request->user(), $request->ip());

        return back()->with('status', 'تم احتساب المسير وتثبيت تفاصيله للمراجعة.');
    }

    public function approve(Request $request, PayrollRun $payrollRun, ApprovePayrollRunAction $action): RedirectResponse
    {
        $this->authorize('approve', $payrollRun);
        $action->execute($payrollRun, $request->user(), $request->ip());

        return back()->with('status', 'تم اعتماد مسير الرواتب.');
    }

    public function markPaid(MarkPayrollRunPaidRequest $request, PayrollRun $payrollRun, MarkPayrollRunPaidAction $action): RedirectResponse
    {
        $action->execute($payrollRun, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تم تسجيل صرف مسير الرواتب.');
    }

    public function close(Request $request, PayrollRun $payrollRun, ClosePayrollRunAction $action): RedirectResponse
    {
        $this->authorize('close', $payrollRun);
        $action->execute($payrollRun, $request->user(), $request->ip());

        return back()->with('status', 'تم إغلاق مسير الرواتب.');
    }

    public function destroy(Request $request, PayrollRun $payrollRun, DeletePayrollRunAction $action): RedirectResponse
    {
        $this->authorize('delete', $payrollRun);
        $action->execute($payrollRun, $request->user(), $request->ip());

        return to_route('dashboard.payroll.index')->with('status', 'تم حذف مسودة المسير.');
    }
}
