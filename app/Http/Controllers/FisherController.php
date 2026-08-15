<?php

namespace App\Http\Controllers;

use App\Models\Fisher;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FisherController extends Controller
{
    public function index(Request $request): View
    {
        $fishers = Fisher::with('port')->orderBy('name')->get();

        $filtered = $fishers
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($f) => str_contains(mb_strtolower($f->name.' '.$f->national_id.' '.$f->license_number), $q));
            })
            ->when($request->filled('port'), fn ($c) => $c->where('port_id', (int) $request->query('port')))
            ->when($request->filled('role'), fn ($c) => $c->where('role', $request->query('role')))
            ->when($request->filled('license'), fn ($c) => $c->where('license_status', $request->query('license')))
            ->values();

        $stats = [
            'total' => $fishers->count(),
            'active' => $fishers->where('status', 'نشط')->count(),
            'captains' => $fishers->where('role', 'كابتن')->count(),
            'valid' => $fishers->where('license_status', 'سارية')->count(),
            'attention' => $fishers->whereIn('license_status', ['قريبة الانتهاء', 'منتهية'])->count(),
        ];

        return view('fishers.index', [
            'fishers' => $filtered,
            'stats' => $stats,
            'ports' => Port::orderBy('name')->get(),
            'roles' => $fishers->pluck('role')->unique()->filter()->values(),
        ]);
    }
}