<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\FishingSite;
use App\Models\Port;
use Illuminate\View\View;

class SeaMapController extends Controller
{
    public function index(): View
    {
        $ports = Port::with('governorate.region')->get();
        $sites = FishingSite::with('port')->get();
        $atSeaBoats = Boat::with('port')->where('status', 'في البحر')->get();

        return view('sea-map.index', compact('ports', 'sites', 'atSeaBoats'));
    }
}