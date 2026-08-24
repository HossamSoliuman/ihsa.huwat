<?php

namespace App\Http\Controllers;

use App\Support\CsvExport;
use App\Support\ReportRegistry;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(): View
    {
        $definitions = collect(ReportRegistry::definitions());
        $counts = ReportRegistry::counts();

        $groups = collect(ReportRegistry::CATEGORIES)->map(function (array $category, string $key) use ($definitions, $counts) {
            $items = $definitions->where('category', $key)
                ->map(fn (array $report, string $id) => $report + ['id' => $id, 'count' => $counts[$id] ?? 0]);

            return $category + [
                'items' => $items->values(),
                'records' => $items->sum('count'),
            ];
        })->filter(fn (array $group) => $group['items']->isNotEmpty())->values();

        return view('reports.index', [
            'groups' => $groups,
            'total' => $definitions->count(),
        ]);
    }

    public function export(string $report): StreamedResponse
    {
        abort_unless(ReportRegistry::exists($report), 404);

        return CsvExport::download(
            ReportRegistry::rows($report),
            $report.'-'.now()->toDateString().'.csv',
        );
    }

    /**
     * نسخة الطباعة — صفحة مستقلة عن تخطيط اللوحة، تُفتح في تبويب جديد ثم تُطبع.
     */
    public function print(string $report): View
    {
        abort_unless(ReportRegistry::exists($report), 404);

        return view('reports.print', [
            'report' => ReportRegistry::definition($report),
            'rows' => ReportRegistry::rows($report)->take(500),
            'total' => ReportRegistry::counts()[$report] ?? 0,
        ]);
    }
}
