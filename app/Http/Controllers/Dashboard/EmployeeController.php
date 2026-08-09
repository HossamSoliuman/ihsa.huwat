<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildEmployeeDirectoryAction;
use App\Actions\BuildEmployeeProfileAction;
use App\Actions\CreateEmployeeAction;
use App\Actions\UpdateEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterEmployeesRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\JobTitle;
use App\Models\Nationality;
use App\Models\Port;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeController extends Controller
{
    public function index(FilterEmployeesRequest $request, BuildEmployeeDirectoryAction $action): View
    {
        return view('dashboard.hr.employees.index', $action->execute($request->validated()));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);

        return view('dashboard.hr.employees.create', [
            'departments' => Department::query()->where('is_active', true)->ordered()->get(),
            'jobTitles' => JobTitle::query()->where('is_active', true)->ordered()->get(),
            'ports' => Port::query()->selectable()->orderBy('name')->get(),
            'nationalities' => Nationality::options(),
            'managers' => Employee::query()->with('user:id,full_name')->whereIn('status', ['active', 'on_leave'])->orderBy('employee_number')->get(),
            'roles' => Role::query()->whereIn('code', ['hr_manager', 'finance_officer', 'port_supervisor', 'stat_employee', 'employee_portal'])->orderBy('id')->get(),
        ]);
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): RedirectResponse
    {
        $employee = $action->execute($request->validated(), $request->user(), $request->ip());

        return to_route('dashboard.hr.employees.show', $employee)
            ->with('status', 'تمت إضافة الموظف بنجاح.');
    }

    public function show(Employee $employee, BuildEmployeeProfileAction $action): View
    {
        return view('dashboard.hr.employees.show', $action->execute($employee, request()->user()));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee, UpdateEmployeeAction $action): RedirectResponse
    {
        $action->execute($employee, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تم تحديث بيانات الموظف.');
    }
}
