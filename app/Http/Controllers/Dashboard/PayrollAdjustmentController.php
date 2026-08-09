<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ApprovePayrollAdjustmentAction;
use App\Actions\CreatePayrollAdjustmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollAdjustmentRequest;
use App\Models\Employee;
use App\Models\PayrollAdjustment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayrollAdjustmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PayrollAdjustment::class);
        $filters = $request->validate([
            'status' => ['nullable', 'in:draft,approved,consumed'],
            'period_year' => ['nullable', 'integer', 'between:2020,2100'],
            'period_month' => ['nullable', 'integer', 'between:1,12'],
        ]);
        $adjustments = PayrollAdjustment::query()
            ->with(['employee.user:id,full_name', 'salaryComponent:id,name_ar', 'creator:id,full_name', 'approver:id,full_name'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['period_year'] ?? null, fn (Builder $query, int|string $year) => $query->where('period_year', $year))
            ->when($filters['period_month'] ?? null, fn (Builder $query, int|string $month) => $query->where('period_month', $month))
            ->latest('period_year')->latest('period_month')->latest('id')
            ->paginate(25)->withQueryString();

        return view('dashboard.hr.adjustments.index', compact('adjustments', 'filters'));
    }

    public function store(StorePayrollAdjustmentRequest $request, Employee $employee, CreatePayrollAdjustmentAction $action): RedirectResponse
    {
        $action->execute([...$request->validated(), 'employee_id' => $employee->id], $request->user(), $request->ip());

        return back()->with('status', 'تمت إضافة التعديل المالي كمسودة.');
    }

    public function approve(Request $request, PayrollAdjustment $payrollAdjustment, ApprovePayrollAdjustmentAction $action): RedirectResponse
    {
        $this->authorize('approve', $payrollAdjustment);
        $action->execute($payrollAdjustment, $request->user(), $request->ip());

        return back()->with('status', 'تم اعتماد التعديل المالي وسيظهر في المسير المحدد.');
    }
}
