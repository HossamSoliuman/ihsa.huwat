<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Support\CsvExport;
use App\Support\ProductionReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportsController extends Controller
{
    public function index(Request $request, ProductionReportService $service): View
    {
        [$period, $year, $month, $day, $region] = $this->filters($request);

        return view('monthly-reports.index', [
            'periods' => ProductionReportService::PERIODS,
            'months' => ProductionReportService::MONTHS,
            'years' => range(now()->year, now()->year - 4),
            'regions' => Region::orderBy('name')->pluck('name'),
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'region' => $region,
            'report' => $service->build($period, $year, $month, $day, $region),
        ]);
    }

    public function export(Request $request, ProductionReportService $service): StreamedResponse
    {
        [$period, $year, $month, $day, $region] = $this->filters($request);
        $report = $service->build($period, $year, $month, $day, $region);

        $rows = $report['by_port']->map(fn (array $row) => [
            'الميناء' => $row['port'],
            'المحافظة' => $row['governorate'],
            'المنطقة' => $row['region'],
            'المصيد (كجم)' => $row['catch_kg'],
            'الرحلات' => $row['trips'],
            'القوارب' => $row['boats'],
            'عدد الأنواع' => $row['species_count'],
        ]);

        return CsvExport::download($rows, 'production-report-'.$this->tag($period, $year, $month, $day).'.csv');
    }

    public function print(Request $request, ProductionReportService $service): View
    {
        [$period, $year, $month, $day, $region] = $this->filters($request);

        return view('monthly-reports.print', [
            'report' => $service->build($period, $year, $month, $day, $region),
            'region' => $region ?: 'كل المناطق',
        ]);
    }

    /**
     * قراءة المرشّحات من الطلب مع تثبيتها داخل حدود صالحة.
     *
     * @return array{0: string, 1: int, 2: int, 3: int, 4: string|null}
     */
    private function filters(Request $request): array
    {
        $period = array_key_exists($request->query('period'), ProductionReportService::PERIODS)
            ? $request->query('period')
            : 'monthly';

        $region = $request->query('region') ?: null;

        return [
            $period,
            (int) $request->query('year', now()->year),
            min(12, max(1, (int) $request->query('month', now()->month))),
            min(31, max(1, (int) $request->query('day', now()->day))),
            $region,
        ];
    }

    private function tag(string $period, int $year, int $month, int $day): string
    {
        return match ($period) {
            'daily' => sprintf('%d-%02d-%02d', $year, $month, $day),
            'yearly' => (string) $year,
            default => sprintf('%d-%02d', $year, $month),
        };
    }
}
