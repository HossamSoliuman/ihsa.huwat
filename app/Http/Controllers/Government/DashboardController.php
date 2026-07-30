<?php

namespace App\Http\Controllers\Government;

use App\Actions\Government\BuildDashboardAction;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(BuildDashboardAction $buildDashboard): View
    {
        return view('government.dashboard', $buildDashboard->handle());
    }
}
