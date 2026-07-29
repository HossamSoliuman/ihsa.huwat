<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\AssignEmployeeAction;
use App\Actions\BuildHumanResourcesDashboardAction;
use App\Actions\CreateEmployeeAction;
use App\Actions\ReviewLeaveAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignEmployeeRequest;
use App\Http\Requests\ReviewLeaveRequest;
use App\Http\Requests\StoreEmployeeRequest;
use App\Models\Leave;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HumanResourcesController extends Controller
{
    public function index(BuildHumanResourcesDashboardAction $action): View
    {
        return view('dashboard.hr.index', $action->execute());
    }

    public function store(StoreEmployeeRequest $request, CreateEmployeeAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('status', 'تمت إضافة الموظف بنجاح.');
    }

    public function review(ReviewLeaveRequest $request, Leave $leave, ReviewLeaveAction $action): RedirectResponse
    {
        $action->execute($leave, $request->string('decision')->toString(), $request->user());

        return back()->with('status', 'تمت مراجعة طلب الإجازة.');
    }

    public function assign(AssignEmployeeRequest $request, AssignEmployeeAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('status', 'تم تكليف الموظف بنجاح.');
    }
}
