<?php

namespace App\Http\Controllers;

use App\Models\BycatchRecord;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BycatchController extends Controller
{
    public function index(Request $request): View
    {
        $records = BycatchRecord::with('trip.boat')->latest()->get();

        $filtered = $records
            ->when($request->filled('action'), fn ($c) => $c->where('action_taken', $request->query('action')))
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($r) => str_contains(mb_strtolower($r->species_name), $q));
            })
            ->values();

        $stats = [
            'total' => $records->count(),
            'quantity' => $records->sum('quantity_kg'),
            'released' => $records->where('action_taken', 'إعادة للبحر')->sum('quantity_kg'),
            'kept' => $records->where('action_taken', 'إنزال')->sum('quantity_kg'),
            'species' => $records->pluck('species_name')->unique()->count(),
        ];

        return view('bycatch.index', [
            'records' => $filtered,
            'stats' => $stats,
            'actions' => $records->pluck('action_taken')->unique()->filter()->values(),
            'trips' => Trip::orderByDesc('departure_time')->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        BycatchRecord::create($request->validate([
            'trip_id' => ['required', 'exists:trips,id'],
            'species_name' => ['required', 'string', 'max:255'],
            'quantity_kg' => ['required', 'numeric', 'min:0'],
            'action_taken' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()->route('bycatch')->with('status', 'تم تسجيل الصيد العرضي بنجاح');
    }
}