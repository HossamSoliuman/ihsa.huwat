<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TripResource;
use App\Services\Captain\CaptainDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CaptainDashboard $dashboard): JsonResponse
    {
        $data = $dashboard->for($request->user());

        return response()->json(['data' => [
            'kpis' => $data['kpis'],
            'pending_trips' => TripResource::collection($data['pending_trips']),
            'active_trips' => TripResource::collection($data['active_trips']),
            'recent_trips' => TripResource::collection($data['recent_trips']),
        ]]);
    }
}
