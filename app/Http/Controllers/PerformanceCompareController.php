<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PerformanceCompareController extends Controller
{
    private const TOP_OPTIONS = [5, 10, 15, 20];

    public function index(Request $request): View
    {
        $view = $request->query('view') === 'port' ? 'port' : 'governorate';
        $topN = in_array((int) $request->query('top'), self::TOP_OPTIONS, true) ? (int) $request->query('top') : 10;

        $ports = Port::with('governorate.region')->get();
        $compliance = $this->complianceByPort();

        $rows = ($view === 'port' ? $this->portRows($ports, $compliance) : $this->governorateRows($ports, $compliance))
            ->filter(fn (array $row) => $row['catch'] > 0 || $row['compliance'] !== null)
            ->sortByDesc('catch')
            ->take($topN)
            ->values();

        $avgCatch = $rows->isNotEmpty() ? $rows->avg('catch') : 0.0;
        $rated = $rows->whereNotNull('compliance');
        $avgCompliance = $rated->isNotEmpty() ? round($rated->avg('compliance')) : 0;

        return view('performance-compare.index', [
            'view' => $view,
            'topN' => $topN,
            'topOptions' => self::TOP_OPTIONS,
            'rows' => $rows->values(),
            'avgCatch' => $avgCatch,
            'avgCompliance' => $avgCompliance,
            'catchGap' => $rows->isNotEmpty() ? $rows->max('catch') - $rows->min('catch') : 0,
            'complianceGap' => $rated->isNotEmpty() ? $rated->max('compliance') - $rated->min('compliance') : 0,
            'topCatchName' => $rows->sortByDesc('catch')->first()['name'] ?? null,
            'lowCatchName' => $rows->sortBy('catch')->first()['name'] ?? null,
            'topComplianceName' => $rated->sortByDesc('compliance')->first()['name'] ?? null,
            'lowComplianceName' => $rated->count() > 1 ? $rated->sortBy('compliance')->first()['name'] : null,
        ]);
    }

    /**
     * نسبة الامتثال لكل ميناء، محسوبة من الرحلات لا من عدّاد مخزّن.
     *
     * الرحلة ممتثلة إذا اعتُمدت ولم يُسجَّل عليها فرق بين إدخال الكابتن والوزن
     * الفعلي. الرحلات المعلّقة تُحسب غير ممتثلة لأنها لم تُغلق بعد.
     *
     * @return Collection<int, int|null> مفتاحها معرّف الميناء
     */
    private function complianceByPort(): Collection
    {
        return Trip::get(['departure_port_id', 'status', 'diff_kg'])
            ->groupBy('departure_port_id')
            ->map(function (Collection $trips) {
                $compliant = $trips->filter(
                    fn ($trip) => $trip->status === 'معتمدة' && (float) $trip->diff_kg === 0.0
                )->count();

                return $trips->count() ? (int) round($compliant / $trips->count() * 100) : null;
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function portRows(Collection $ports, Collection $compliance): Collection
    {
        return $ports->map(fn (Port $port) => [
            'id' => 'port-'.$port->id,
            'name' => $port->name,
            'sub' => $port->governorate?->region?->name ?? '—',
            'catch' => round((float) $port->total_catch_tons, 1),
            'compliance' => $compliance[$port->id] ?? null,
            'trips' => $port->monthly_trips,
            'boats' => $port->active_boats,
        ]);
    }

    /**
     * المحافظة تُجمَّع من موانئها: الكمية بالجمع، والامتثال بالمتوسط المرجّح بعدد الرحلات.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function governorateRows(Collection $ports, Collection $compliance): Collection
    {
        return $ports->groupBy('governorate_id')->map(function (Collection $group) use ($compliance) {
            $governorate = $group->first()->governorate;
            $rated = $group->filter(fn (Port $port) => isset($compliance[$port->id]));

            return [
                'id' => 'gov-'.($governorate?->id ?? 0),
                'name' => $governorate?->name ?? 'غير محدد',
                'sub' => $governorate?->region?->name ?? '—',
                'catch' => round((float) $group->sum('total_catch_tons'), 1),
                'compliance' => $rated->isNotEmpty()
                    ? (int) round($rated->avg(fn (Port $port) => $compliance[$port->id]))
                    : null,
                'trips' => $group->sum('monthly_trips'),
                'boats' => $group->sum('active_boats'),
            ];
        })->values();
    }
}
