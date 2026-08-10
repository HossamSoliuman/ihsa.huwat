<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildEmployeeDirectoryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterEmployeesRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeExportController extends Controller
{
    public function __invoke(FilterEmployeesRequest $request, BuildEmployeeDirectoryAction $action): StreamedResponse
    {
        $query = $action->query($request->validated());
        $filename = 'ihsa-employees-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['الرقم الوظيفي', 'الاسم', 'رقم الهوية', 'القسم', 'المسمى الوظيفي', 'الميناء', 'نوع العقد', 'الحالة']);

            $query->chunkById(200, function ($employees) use ($stream): void {
                foreach ($employees as $employee) {
                    fputcsv($stream, [
                        $employee->employee_number,
                        $employee->user?->full_name,
                        $employee->national_id,
                        $employee->department?->name,
                        $employee->jobTitle?->name,
                        $employee->port?->name,
                        $employee->activeContract?->contract_type,
                        $employee->status,
                    ]);
                }
            });

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
