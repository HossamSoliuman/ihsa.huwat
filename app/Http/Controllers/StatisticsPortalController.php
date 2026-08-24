<?php

namespace App\Http\Controllers;

use App\Support\StatisticsSection;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticsPortalController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->query('q');

        return view('statistics.index', [
            'query' => $query,
            'groups' => StatisticsSection::search($query),
            'dashboards' => StatisticsSection::dashboardCount(),
            'groupCount' => count(StatisticsSection::groups()),
        ]);
    }
}
