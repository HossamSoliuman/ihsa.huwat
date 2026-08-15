<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatchTraceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $trip = null;
        if ($search) {
            $trip = Trip::with(['boat.port.governorate.region', 'departurePort', 'catchRecords.species'])
                ->where('trip_number', 'like', "%{$search}%")
                ->first();
        }

        $recent = Trip::with('boat')->where('status', 'معتمدة')->orderByDesc('return_time')->limit(8)->get();

        return view('catch-trace.index', compact('trip', 'recent', 'search'));
    }
}