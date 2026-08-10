<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ChangeEmployeeSalaryComponentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmployeeSalaryRequest;
use App\Models\Employee;
use App\Models\SalaryComponent;
use Illuminate\Http\RedirectResponse;

class EmployeeSalaryController extends Controller
{
    public function store(
        UpdateEmployeeSalaryRequest $request,
        Employee $employee,
        SalaryComponent $salaryComponent,
        ChangeEmployeeSalaryComponentAction $action,
    ): RedirectResponse {
        $action->execute($employee, $salaryComponent, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تم حفظ التغيير كسجل راتب جديد دون تعديل التاريخ السابق.');
    }
}
