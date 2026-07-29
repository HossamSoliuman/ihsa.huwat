<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildEmployeePerformanceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterEmployeePerformanceRequest;
use Illuminate\Contracts\View\View;

class EmployeePerformanceController extends Controller
{
    public function __invoke(FilterEmployeePerformanceRequest $request, BuildEmployeePerformanceAction $action): View
    {
        return view('dashboard.employee-performance.index', $action->execute($request->user(), $request->validated()));
    }
}
