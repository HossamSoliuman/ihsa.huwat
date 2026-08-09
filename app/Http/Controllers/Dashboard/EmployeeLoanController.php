<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ApproveEmployeeLoanAction;
use App\Actions\CreateEmployeeLoanAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeLoanRequest;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeLoanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', EmployeeLoan::class);
        $filters = $request->validate(['status' => ['nullable', 'in:requested,approved,active,completed,cancelled']]);
        $loans = EmployeeLoan::query()
            ->with(['employee.user:id,full_name', 'approver:id,full_name', 'instalments'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)->withQueryString();

        return view('dashboard.hr.loans.index', compact('loans', 'filters'));
    }

    public function store(StoreEmployeeLoanRequest $request, Employee $employee, CreateEmployeeLoanAction $action): RedirectResponse
    {
        $action->execute([...$request->validated(), 'employee_id' => $employee->id], $request->user(), $request->ip());

        return back()->with('status', 'تم تسجيل طلب السلفة.');
    }

    public function approve(Request $request, EmployeeLoan $employeeLoan, ApproveEmployeeLoanAction $action): RedirectResponse
    {
        $this->authorize('approve', $employeeLoan);
        $action->execute($employeeLoan, $request->user(), $request->ip());

        return back()->with('status', 'تم اعتماد السلفة وجدولة أقساطها.');
    }
}
