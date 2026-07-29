<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildReportAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterReportsRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function __invoke(FilterReportsRequest $request, BuildReportAction $action): StreamedResponse
    {
        $report = $action->execute($request->user(), $request->validated());
        $filename = "ihsa-{$request->validated('report_type')}-".now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $report['columns']);
            foreach ($report['rows'] as $row) {
                fputcsv($stream, $row);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
