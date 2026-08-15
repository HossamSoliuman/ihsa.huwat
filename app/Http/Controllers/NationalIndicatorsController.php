<?php

namespace App\Http\Controllers;

use App\Support\ExecutiveKpiService;
use Illuminate\View\View;

class NationalIndicatorsController extends Controller
{
    public function index(ExecutiveKpiService $service): View
    {
        $data = $service->build();
        $byKey = collect($data['kpis'])->keyBy('key');

        $registry = [
            ['title' => 'الإنتاج والرحلات', 'keys' => ['total_approved_catch', 'total_trips', 'returned_boats', 'top_producing_region']],
            ['title' => 'الأسطول والقوى العاملة', 'keys' => ['active_boats', 'boats_at_sea', 'active_fishers']],
            ['title' => 'الإحصاء والاعتماد', 'keys' => ['pending_statistics_trips', 'pending_approval_trips', 'avg_statistics_discrepancy', 'approved_catch_share']],
            ['title' => 'الأسواق والرقابة وجودة البيانات', 'keys' => ['avg_fish_price', 'violations_count', 'traceability_completeness', 'data_quality_score']],
        ];

        $groups = array_map(fn ($group) => [
            'title' => $group['title'],
            'items' => collect($group['keys'])->map(fn ($key) => $byKey[$key] ?? null)->filter()->values(),
        ], $registry);

        return view('national-indicators.index', [
            'groups' => $groups,
            'byRegion' => $data['byRegion'],
            'total' => count($data['kpis']),
        ]);
    }
}