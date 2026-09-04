<?php

namespace App\Http\Controllers;

use App\Models\CatchRecord;
use App\Models\Governorate;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortController extends Controller
{
    public function index(Request $request): View
    {
        $ports = Port::with('governorate.region')->orderBy('name')->get();

        $filtered = $ports
            ->when($request->filled('search'), function ($c) use ($request) {
                $q = mb_strtolower($request->query('search'));
                return $c->filter(fn ($p) => str_contains(mb_strtolower($p->name.' '.$p->code), $q));
            })
            ->when($request->filled('governorate'), fn ($c) => $c->where('governorate_id', (int) $request->query('governorate')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $stats = [
            'total' => $ports->count(),
            'boats' => $ports->sum('boats_count'),
            'active' => $ports->sum('active_boats'),
            'fishers' => $ports->sum('fishers_count'),
            'catch' => $ports->sum('total_catch_tons'),
            'staff' => $ports->sum('statistics_staff'),
        ];

        return view('ports.index', [
            'ports' => $filtered,
            'stats' => $stats,
            'governorates' => Governorate::orderBy('name')->get(),
        ]);
    }

    /**
     * صفحة الميناء الواحد: كل ما يُسجَّل تحته — أسطوله وصيّادوه ومواقع صيده
     * ورحلاته ومصيده — في مكان واحد بدل تفتيته على شاشات الأسطول والرحلات.
     */
    public function show(Port $port): View
    {
        $port->load('governorate.region');

        $boats = $port->boats()->orderBy('name')->get();
        $fishers = $port->fishers()->orderBy('name')->get();
        $officers = $port->statisticsOfficers()->orderBy('name')->get();
        $sites = $port->fishingSites()->orderByDesc('catch_kg')->get();
        $trips = $port->trips()->with('boat')->orderByDesc('departure_time')->get();

        $catches = CatchRecord::with('species')
            ->whereHas('trip', fn ($q) => $q->where('departure_port_id', $port->id))
            ->get();

        /* آخر أربعة عشر يومًا فيها تسجيل — لا آخر أربعة عشر يومًا تقويميًا. */
        $trend = $catches->groupBy(fn ($c) => $c->recorded_at->toDateString())
            ->map(fn ($g) => round($g->sum('quantity_kg')))
            ->sortKeys()
            ->take(-14);

        $topSpecies = $catches->groupBy(fn ($c) => $c->species?->name_ar ?? 'غير محدّد')
            ->map(fn ($g) => round($g->sum('quantity_kg')))
            ->sortDesc()
            ->take(8);

        return view('ports.show', [
            'port' => $port,
            'boats' => $boats,
            'fishers' => $fishers,
            'officers' => $officers,
            'sites' => $sites,
            'trips' => $trips,
            'trend' => $trend,
            'topSpecies' => $topSpecies,
            'boatStatuses' => $boats->countBy('status'),
            'tripStatuses' => $trips->countBy('status'),
            'catchKg' => round($catches->sum('quantity_kg')),
        ]);
    }

    public function compare(Request $request): View
    {
        $ports = Port::with('governorate.region')->orderByDesc('total_catch_tons')->get();
        $metric = $request->query('metric', 'total_catch_tons');

        $metrics = [
            'total_catch_tons' => 'إجمالي المصيد (طن)',
            'boats_count' => 'عدد القوارب',
            'active_boats' => 'القوارب النشطة',
            'fishers_count' => 'عدد الصيادين',
            'daily_trips' => 'الرحلات اليومية',
            'monthly_trips' => 'الرحلات الشهرية',
        ];

        $selected = array_key_exists($metric, $metrics) ? $metric : 'total_catch_tons';
        $ranked = $ports->sortByDesc($selected)->values();

        return view('ports-compare.index', [
            'ports' => $ranked,
            'metrics' => $metrics,
            'metric' => $selected,
            'chart' => $ranked->take(10)->mapWithKeys(fn ($p) => [$p->name => (float) $p->{$selected}]),
        ]);
    }
}