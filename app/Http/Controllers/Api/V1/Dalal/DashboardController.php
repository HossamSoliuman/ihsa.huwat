<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PartnershipResource;
use App\Http\Resources\Api\SaleResource;
use App\Services\Dalal\DalalDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, DalalDashboard $dashboard): JsonResponse
    {
        $data = $dashboard->for($request->user(), (string) $request->query('period', 'month'), $request->query('from'), $request->query('to'));

        return response()->json(['data' => [
            'period' => $data['period'],
            'kpis' => $data['kpis'],
            'revenue_by_month' => $data['revenue_by_month'],
            'top_species' => $data['top_species'],
            'pending_requests' => PartnershipResource::collection($data['pending_requests']),
            'recent_sales' => SaleResource::collection($data['recent_sales']),
        ]]);
    }
}
