<?php

namespace App\Http\Controllers;

use App\Actions\Information\Dashboard\BuildInformationDashboard;
use App\Http\Requests\FilterInformationDashboardRequest;
use Illuminate\Contracts\View\View;

class InformationDashboardController extends Controller
{
    public function __invoke(
        FilterInformationDashboardRequest $request,
        BuildInformationDashboard $buildInformationDashboard,
    ): View {
        return view('information.admin.dashboard', $buildInformationDashboard->execute($request->validated()));
    }
}
