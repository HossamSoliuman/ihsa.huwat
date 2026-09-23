<?php

namespace App\Http\Controllers\Api\V1\Counter;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\TripResource;
use App\Services\Counter\CounterDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, CounterDashboard $dashboard): JsonResponse
    {
        $data = $dashboard->for($request->user());
        $port = $data['port'];

        return response()->json(['data' => [
            'port' => $port ? ['id' => $port->id, 'name' => $port->name, 'governorate' => $port->governorate?->name] : null,
            'kpis' => $data['kpis'],
            'awaiting_trips' => TripResource::collection($data['awaiting_trips']),
            'counting_trips' => TripResource::collection($data['counting_trips']),
            'recent_trips' => TripResource::collection($data['recent_trips']),
        ]]);
    }
}
