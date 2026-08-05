<?php

namespace App\Http\Controllers;

use App\Actions\BuildInformationDashboardAction;
use App\Http\Requests\FilterInformationDashboardRequest;
use Illuminate\Contracts\View\View;

class InformationDashboardController extends Controller
{
    public function __invoke(
        FilterInformationDashboardRequest $request,
        BuildInformationDashboardAction $buildInformationDashboard,
    ): View {
        return view('information.admin.dashboard', $buildInformationDashboard->execute($request->validated()));
    }
}
