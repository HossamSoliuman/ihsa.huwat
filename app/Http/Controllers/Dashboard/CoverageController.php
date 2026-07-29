<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildCoverageDashboardAction;
use App\Actions\StoreSubstituteAssignmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterCoverageRequest;
use App\Http\Requests\StoreSubstituteAssignmentRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CoverageController extends Controller
{
    public function index(FilterCoverageRequest $request, BuildCoverageDashboardAction $action): View
    {
        return view('dashboard.coverage.index', $action->execute($request->user(), $request->validated()));
    }

    public function store(StoreSubstituteAssignmentRequest $request, StoreSubstituteAssignmentAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return back()->with('status', 'تم تكليف الموظف بنجاح.');
    }
}
