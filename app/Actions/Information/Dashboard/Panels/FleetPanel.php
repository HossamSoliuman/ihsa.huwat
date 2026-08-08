<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Models\Boat;
use App\Models\FishingMethod;
use App\Models\InformationSubmission;
use App\Models\Nationality;
use Illuminate\Support\Collection;

final class FleetPanel
{
    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $submissions = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->where('status', 'approved');
        $boatTypes = (clone $submissions)
            ->toBase()
            ->join((new Boat)->getTable(), 'boats.id', '=', 'information_submissions.boat_id')
            ->selectRaw('boats.boat_type AS code, COUNT(*) AS aggregate')
            ->groupBy('boats.boat_type')
            ->orderByDesc('aggregate')
            ->get();
        $fishingMethods = (clone $submissions)->toBase()
            ->whereNotNull('fishing_method')
            ->selectRaw('fishing_method AS code, COUNT(*) AS aggregate')
            ->groupBy('fishing_method')
            ->orderByDesc('aggregate')
            ->get();
        $ownerNationalities = (clone $submissions)->toBase()
            ->whereNotNull('owner_nationality')
            ->selectRaw('owner_nationality AS code, COUNT(*) AS aggregate')
            ->groupBy('owner_nationality')
            ->orderByDesc('aggregate')
            ->limit(8)
            ->get();
        $crewBuckets = (clone $submissions)->toBase()
            ->selectRaw("CASE WHEN crew_count <= 3 THEN '1_3' WHEN crew_count <= 6 THEN '4_6' WHEN crew_count <= 10 THEN '7_10' ELSE '11_plus' END AS bucket")
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        return [
            'fleet' => [
                'boatTypes' => $this->items($boatTypes, [
                    'large' => 'كبير', 'small' => 'صغير', 'recreational' => 'نزهة', 'unclassified' => 'غير مصنف',
                ]),
                'fishingMethods' => $this->items($fishingMethods, FishingMethod::labels(), 'slot-2'),
                'ownerNationalities' => $this->items($ownerNationalities, Nationality::labels(), 'slot-3'),
                'crewBuckets' => collect([
                    '1_3' => '1–3 بحارة',
                    '4_6' => '4–6 بحارة',
                    '7_10' => '7–10 بحارة',
                    '11_plus' => '11 فأكثر',
                ])->map(fn (string $label, string $key): array => [
                    'label' => $label,
                    'value' => (int) $crewBuckets->get($key, 0),
                    'tone' => 'slot-4',
                ])->values()->all(),
            ],
        ];
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  array<string, string>  $labels
     * @return list<array{label: string, value: int, tone: string}>
     */
    private function items(Collection $rows, array $labels, string $tone = 'slot-1'): array
    {
        return $rows->map(fn (object $row): array => [
            'label' => $labels[$row->code] ?? $row->code,
            'value' => (int) $row->aggregate,
            'tone' => $tone,
        ])->all();
    }
}
