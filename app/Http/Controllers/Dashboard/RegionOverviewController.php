<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildRegionOverviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ViewRegionOverviewRequest;
use Illuminate\Contracts\View\View;

class RegionOverviewController extends Controller
{
    public function __invoke(ViewRegionOverviewRequest $request, BuildRegionOverviewAction $action): View
    {
        return view('dashboard.region-overview.index', $action->execute($request->user(), $request->validated()));
    }
}
