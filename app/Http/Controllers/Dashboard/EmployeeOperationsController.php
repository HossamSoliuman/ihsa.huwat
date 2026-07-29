<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildEmployeeOperationsDashboardAction;
use App\Actions\RecordTripCatchAction;
use App\Actions\StartTripCountingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartTripCountingRequest;
use App\Http\Requests\SubmitTripCatchRequest;
use App\Http\Requests\ViewEmployeeOperationsRequest;
use App\Models\Trip;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmployeeOperationsController extends Controller
{
    public function index(ViewEmployeeOperationsRequest $request, BuildEmployeeOperationsDashboardAction $action): View
    {
        return view('dashboard.employee-operations.index', $action->execute($request->user()));
    }

    public function start(StartTripCountingRequest $request, Trip $trip, StartTripCountingAction $action): RedirectResponse
    {
        $action->execute($trip, $request->user());

        return back()->with('status', 'تم بدء إحصاء الرحلة.');
    }

    public function storeCatch(SubmitTripCatchRequest $request, Trip $trip, RecordTripCatchAction $action): RedirectResponse
    {
        $updatedTrip = $action->execute($trip, $request->user(), $request->validated('catches'));
        $message = $updatedTrip->status === 'pending_review'
            ? 'تم حفظ المصيد وتحويل الرحلة إلى مراجعة المشرف.'
            : 'تم حفظ المصيد واعتماد الرحلة.';

        return back()->with('status', $message);
    }
}
