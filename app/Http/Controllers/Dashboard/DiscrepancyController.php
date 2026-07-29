<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ApproveTripDiscrepancyAction;
use App\Actions\BuildDiscrepancyDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApproveDiscrepancyRequest;
use App\Http\Requests\FilterDiscrepanciesRequest;
use App\Models\TripDiscrepancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class DiscrepancyController extends Controller
{
    public function index(FilterDiscrepanciesRequest $request, BuildDiscrepancyDashboardAction $action): View
    {
        return view('dashboard.discrepancies.index', $action->execute($request->user(), $request->validated()));
    }

    public function approve(ApproveDiscrepancyRequest $request, TripDiscrepancy $discrepancy, ApproveTripDiscrepancyAction $action): RedirectResponse
    {
        $action->execute($discrepancy, $request->user());

        return back()->with('status', 'تم اعتماد الفرق والرحلة بنجاح.');
    }
}
