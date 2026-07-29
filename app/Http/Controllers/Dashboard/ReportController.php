<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterReportsRequest;
use Illuminate\Contracts\View\View;

class ReportController extends Controller
{
    public function index(FilterReportsRequest $request, BuildReportAction $action): View
    {
        return view('dashboard.reports.index', $action->execute($request->user(), $request->validated()));
    }
}
