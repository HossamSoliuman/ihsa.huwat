<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildAdminDashboardAction;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __invoke(BuildAdminDashboardAction $buildDashboard): View
    {
        return view('dashboard.admin', $buildDashboard->handle());
    }
}
