<?php

namespace App\Http\Controllers;

use App\Models\CatchRecord;
use App\Models\Trip;
use App\Support\CsvExport;
use App\Support\ExecutiveKpiService;
use App\Support\ProductionReportService;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExecutiveBriefingController extends Controller
{
    /** المؤشرات الثمانية التي يقوم عليها الموجز، بترتيب عرضها. */
    private const STRATEGIC = [
        'total_approved_catch', 'total_trips', 'active_boats', 'active_fishers',
        'approved_catch_share', 'avg_fish_price', 'violations_count', 'data_quality_score',
    ];

    public function index(ExecutiveKpiService $service): View
    {
        return view('executive-briefing.index', $this->briefing($service));
    }

    public function exportCsv(ExecutiveKpiService $service): StreamedResponse
    {
        $data = $this->briefing($service);

        $rows = collect($data['kpis'])->map(fn (array $kpi) => [
            'المفتاح' => $kpi['key'],
            'المؤشر' => $kpi['label'],
            'القيمة' => $kpi['value'],
            'الوحدة' => $kpi['unit'],
        ]);

        $rows = $rows
            ->concat($data['byRegion']->map(fn (float $tons, string $region) => [
                'المفتاح' => 'by_region', 'المؤشر' => $region, 'القيمة' => $tons, 'الوحدة' => 'طن',
            ])->values())
            ->concat($data['trend']->map(fn (array $point) => [
                'المفتاح' => 'monthly', 'المؤشر' => $point['label'], 'القيمة' => $point['value'], 'الوحدة' => 'طن',
            ]))
            ->concat($data['fleet']->map(fn (int $count, string $status) => [
                'المفتاح' => 'fleet_status', 'المؤشر' => $status, 'القيمة' => $count, 'الوحدة' => 'رحلة',
            ])->values());

        return CsvExport::download($rows, 'hawat-executive-briefing-'.now()->toDateString().'.csv');
    }

    public function exportJson(ExecutiveKpiService $service): StreamedResponse
    {
        $data = $this->briefing($service);

        $payload = [
            'meta' => [
                'app' => config('hawat.name'),
                'page' => 'موجز الإدارة العليا',
                'scope' => $data['scope'],
                'generated_at' => now()->toIso8601String(),
                'period_days' => 30,
            ],
            'kpis' => $data['kpis'],
            'charts' => [
                'by_region' => $data['byRegion'],
                'monthly' => $data['trend'],
                'fleet_status' => $data['fleet'],
            ],
        ];

        return response()->streamDownload(
            fn () => print (json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)),
            'hawat-executive-briefing-'.now()->toDateString().'.json',
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /**
     * الحمولة المشتركة بين العرض والتصديرين، حتى لا يفترق ملف التصدير عن الشاشة.
     *
     * @return array<string, mixed>
     */
    private function briefing(ExecutiveKpiService $service): array
    {
        $data = $service->build();
        $byKey = collect($data['kpis'])->keyBy('key');

        return [
            'kpis' => $data['kpis'],
            'strategic' => collect(self::STRATEGIC)->map(fn (string $key) => $byKey[$key] ?? null)->filter()->values(),
            'byRegion' => $data['byRegion'],
            'trend' => $this->trend(),
            'fleet' => $this->fleet(),
            'scope' => 'المملكة',
            'generatedAt' => now(),
        ];
    }

    /**
     * إنتاج آخر اثني عشر شهرًا بالطن.
     *
     * @return Collection<int, array{label: string, value: float}>
     */
    private function trend(): Collection
    {
        /*
         * الطرح يبدأ من أول الشهر لا من اليوم الجاري: في يوم 31 يفيض
         * `subMonths` على الشهر التالي، فتتكرّر أشهر وتسقط أخرى من السلسلة.
         */
        $firstMonth = now()->startOfMonth()->subMonths(11);

        $byMonth = CatchRecord::where('recorded_at', '>=', $firstMonth)
            ->get(['recorded_at', 'quantity_kg'])
            ->groupBy(fn ($record) => $record->recorded_at->format('Y-n'));

        return collect(range(11, 0))->map(function (int $back) use ($byMonth) {
            $month = now()->startOfMonth()->subMonths($back);
            $bucket = $byMonth[$month->format('Y-n')] ?? collect();

            return [
                'label' => ProductionReportService::MONTHS[(int) $month->format('n')],
                'value' => round((float) $bucket->sum('quantity_kg') / 1000, 2),
            ];
        })->values();
    }

    /**
     * توزيع الرحلات على الحالات التشغيلية.
     *
     * @return Collection<string, int>
     */
    private function fleet(): Collection
    {
        return Trip::get(['status'])
            ->groupBy('status')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc();
    }
}
