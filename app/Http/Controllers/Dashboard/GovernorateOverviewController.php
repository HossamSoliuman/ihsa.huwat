<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildGovernorateOverviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ViewGovernorateOverviewRequest;
use Illuminate\Contracts\View\View;

class GovernorateOverviewController extends Controller
{
    public function __invoke(ViewGovernorateOverviewRequest $request, BuildGovernorateOverviewAction $action): View
    {
        return view('dashboard.governorate-overview.index', $action->execute($request->user(), $request->validated()));
    }
}
