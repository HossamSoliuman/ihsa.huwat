<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildAlertsDashboardAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ViewAlertsRequest;
use Illuminate\Contracts\View\View;

class AlertController extends Controller
{
    public function __invoke(ViewAlertsRequest $request, BuildAlertsDashboardAction $action): View
    {
        return view('dashboard.alerts.index', $action->execute($request->user()));
    }
}
