<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\UpdateEmployeeBankDetailsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateEmployeeBankDetailsRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;

class EmployeeBankDetailsController extends Controller
{
    public function update(UpdateEmployeeBankDetailsRequest $request, Employee $employee, UpdateEmployeeBankDetailsAction $action): RedirectResponse
    {
        $action->execute($employee, $request->validated(), $request->user(), $request->ip());

        return back()->with('status', 'تم تحديث البيانات البنكية.');
    }
}
