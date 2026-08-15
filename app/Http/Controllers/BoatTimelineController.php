<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BoatTimelineController extends Controller
{
    public function index(Request $request): View
    {
        $boats = Boat::with('port')->orderBy('name')->get();
        $boat = $request->filled('boat') ? $boats->firstWhere('id', (int) $request->query('boat')) : $boats->first();

        $trips = $boat
            ? $boat->trips()->with('catchRecords.species')->orderByDesc('departure_time')->get()
            : collect();

        return view('boat-timeline.index', [
            'boats' => $boats,
            'boat' => $boat,
            'trips' => $trips,
            'totalApproved' => $trips->sum('approved_kg'),
            'avgDuration' => round((float) $trips->avg('duration_hours'), 1),
        ]);
    }
}