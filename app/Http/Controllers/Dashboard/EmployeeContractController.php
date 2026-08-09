<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\CreateEmployeeContractAction;
use App\Actions\RenewEmployeeContractAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeContractRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;

class EmployeeContractController extends Controller
{
    public function store(
        StoreEmployeeContractRequest $request,
        Employee $employee,
        CreateEmployeeContractAction $action,
    ): RedirectResponse {
        $action->execute($employee, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تمت إضافة العقد بنجاح.');
    }

    public function renew(
        StoreEmployeeContractRequest $request,
        Employee $employee,
        RenewEmployeeContractAction $action,
    ): RedirectResponse {
        $action->execute($employee, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تم تجديد العقد بنجاح.');
    }
}
