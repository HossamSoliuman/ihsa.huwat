<?php

namespace App\Services\Counters;

use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\StatisticsOfficer;
use App\Models\Trip;
use Illuminate\Support\Collection;

/**
 * أرقام بوابة شركة التشغيل: موانئها وعدّادوها وطلبات التوظيف وجولاتها،
 * ونشاط كل عدّاد (ما عدّه من رحلات وكيلوغرامات وآخر عدّ) — النشاط وحده،
 * لا مبيعات الملاك ولا تفاصيل رحلاتهم.
 */
class CompanyDashboard
{
    public function for(OperatingCompany $company): array
    {
        $counters = $company->counters();
        $pending = $company->applications()->verified()->where('status', CounterApplication::PENDING);

        return [
            'company' => $company,
            'kpis' => [
                'ports' => $company->ports()->count(),
                'counters' => (clone $counters)->whereNull('suspended_at')->count(),
                'suspended' => (clone $counters)->whereNotNull('suspended_at')->count(),
                'pending' => (clone $pending)->count(),
                'open_rounds' => $company->hiringRounds()->accepting()->count(),
                'counted_month' => $this->countedTrips($company)->where('counted_at', '>=', now()->startOfMonth())->count(),
            ],
            'pending_applications' => (clone $pending)->with(['port', 'round'])->oldest()->limit(5)->get(),
            'rounds' => $company->hiringRounds()
                ->with('port')
                ->withCount(['applications as approved_count' => fn ($q) => $q->where('status', CounterApplication::APPROVED)])
                ->where('status', HiringRound::OPEN)
                ->orderBy('closes_at')
                ->get(),
            'activity' => $this->activity($company),
        ];
    }

    /**
     * نشاط كل عدّاد في الشركة: رحلاته المعدودة ووزنها وآخر عدّ، الأنشط أولًا.
     *
     * @return Collection<int, StatisticsOfficer>
     */
    public function activity(OperatingCompany $company, ?int $limit = 8): Collection
    {
        $officers = $company->counters()->with(['port', 'user'])->get();
        $userIds = $officers->pluck('user_id')->filter()->all();

        $stats = Trip::whereIn('counter_id', $userIds)
            ->whereNotNull('counted_at')
            ->selectRaw('counter_id, COUNT(*) AS trips, SUM(actual_weight_kg) AS kg, MAX(counted_at) AS last_counted_at')
            ->groupBy('counter_id')
            ->get()
            ->keyBy('counter_id');

        $officers->each(function (StatisticsOfficer $officer) use ($stats) {
            $row = $stats[$officer->user_id] ?? null;
            $officer->setAttribute('activity_trips', (int) ($row->trips ?? 0));
            $officer->setAttribute('activity_kg', round((float) ($row->kg ?? 0), 1));
            $officer->setAttribute('activity_last', $row?->last_counted_at);
        });

        $sorted = $officers->sortByDesc('activity_trips')->values();

        return $limit ? $sorted->take($limit) : $sorted;
    }

    private function countedTrips(OperatingCompany $company)
    {
        return Trip::whereIn('counter_id', $company->counters()->whereNotNull('user_id')->select('user_id'));
    }
}
