<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Region;
use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    private const TYPES = [
        'region' => 'المناطق',
        'species' => 'الأنواع السمكية',
        'port' => 'الموانئ',
    ];

    public function index(Request $request): View
    {
        $type = array_key_exists($request->query('type'), self::TYPES) ? $request->query('type') : 'region';
        $options = $this->options($type);

        $first = $request->query('first');
        $second = $request->query('second');

        $firstValue = $options[$first] ?? null;
        $secondValue = $options[$second] ?? null;
        $comparable = $firstValue !== null && $secondValue !== null;
        $difference = $comparable ? $secondValue - $firstValue : 0.0;

        return view('analytics.index', [
            'types' => self::TYPES,
            'type' => $type,
            'options' => $options,
            'first' => $firstValue !== null ? $first : null,
            'second' => $secondValue !== null ? $second : null,
            'firstValue' => $firstValue ?? 0.0,
            'secondValue' => $secondValue ?? 0.0,
            'comparable' => $comparable,
            'difference' => round($difference, 1),
            'differencePct' => $comparable && $firstValue > 0 ? round($difference / $firstValue * 100, 1) : 0,
            'top' => $options->sortDesc()->take(10),
        ]);
    }

    /**
     * كمية المصيد بالطن لكل عنصر من النوع المختار.
     *
     * @return Collection<string, float>
     */
    private function options(string $type): Collection
    {
        return match ($type) {
            'species' => Species::orderByDesc('catch_kg')->get()
                ->mapWithKeys(fn (Species $s) => [$s->name_ar => round((float) $s->catch_kg / 1000, 1)]),
            'port' => Port::orderByDesc('total_catch_tons')->get()
                ->mapWithKeys(fn (Port $p) => [$p->name => round((float) $p->total_catch_tons, 1)]),
            default => Region::orderByDesc('total_catch_tons')->get()
                ->mapWithKeys(fn (Region $r) => [$r->name => round((float) $r->total_catch_tons, 1)]),
        };
    }
}
