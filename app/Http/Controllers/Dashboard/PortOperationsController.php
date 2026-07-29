<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildPortOperationsAction;
use App\Actions\ManagePortOperationsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ApprovePortDiscrepancyRequest;
use App\Http\Requests\AssignPortTripRequest;
use App\Http\Requests\StorePortAssignmentRequest;
use App\Http\Requests\TransferPortTripRequest;
use App\Http\Requests\ViewPortOperationsRequest;
use App\Models\Port;
use App\Models\Trip;
use App\Models\TripDiscrepancy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PortOperationsController extends Controller
{
    public function index(ViewPortOperationsRequest $request, BuildPortOperationsAction $action): View
    {
        return view('dashboard.port-operations.index', $action->execute($request->user(), $request->validated()));
    }

    public function assignTrip(AssignPortTripRequest $request, Port $port, Trip $trip, ManagePortOperationsAction $action): RedirectResponse
    {
        $action->assignTrip($port, $trip, $request->integer('employee_id'));

        return $this->redirect($port, 'تم إسناد الرحلة للموظف.');
    }

    public function transfer(TransferPortTripRequest $request, Port $port, Trip $trip, ManagePortOperationsAction $action): RedirectResponse
    {
        $action->transferToReview($port, $trip);

        return $this->redirect($port, 'تم تحويل الرحلة للمراجعة.');
    }

    public function approve(ApprovePortDiscrepancyRequest $request, Port $port, TripDiscrepancy $discrepancy, ManagePortOperationsAction $action): RedirectResponse
    {
        $action->approveDiscrepancy($request->user(), $port, $discrepancy);

        return $this->redirect($port, 'تم اعتماد الفرق والرحلة.');
    }

    public function storeAssignment(StorePortAssignmentRequest $request, Port $port, ManagePortOperationsAction $action): RedirectResponse
    {
        $action->addAssignment($port, $request->validated());

        return $this->redirect($port, 'تمت إضافة الموظف إلى مناوبة اليوم.');
    }

    private function redirect(Port $port, string $message): RedirectResponse
    {
        return to_route('dashboard.port-operations.index', ['port_id' => $port->id])->with('status', $message);
    }
}
