<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildEmploymentProfileAction;
use App\Actions\CreateEmployeeLeaveAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeLeaveRequest;
use App\Http\Requests\ViewEmploymentProfileRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmploymentProfileController extends Controller
{
    public function show(ViewEmploymentProfileRequest $request, BuildEmploymentProfileAction $action): View
    {
        return view('dashboard.employment-profile.show', $action->execute($request->user()));
    }

    public function storeLeave(StoreEmployeeLeaveRequest $request, CreateEmployeeLeaveAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->validated());

        return back()->with('status', 'تم إرسال طلب الإجازة إلى الموارد البشرية.');
    }
}
