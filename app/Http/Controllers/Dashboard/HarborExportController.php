<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildHarborWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\ViewHarborRequest;
use App\Models\Port;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HarborExportController extends Controller
{
    public function __invoke(ViewHarborRequest $request, Port $port, BuildHarborWorkspaceAction $action): StreamedResponse
    {
        $data = $action->execute($request->user(), $port);
        $harbor = $data['harbor'];
        $boatTypes = $data['boatTypes'];
        $filename = 'harbor-'.Str::slug($harbor->name).'.csv';

        return response()->streamDownload(function () use ($harbor, $boatTypes): void {
            $stream = fopen('php://output', 'wb');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['تقرير المرفأ', $harbor->name]);
            fputcsv($stream, ['المنطقة', $harbor->governorate->region->name, 'المحافظة', $harbor->governorate->name, 'الموقع', $harbor->location_name]);
            fputcsv($stream, ['الاستيعاب', $boatTypes->sum('capacity'), 'الشاغلة', $boatTypes->sum('occupied'), 'المعطلة', $boatTypes->sum('disabled')]);
            fputcsv($stream, []);
            fputcsv($stream, ['القارب', 'رقم التسجيل', 'النوع', 'الحالة']);
            foreach ($harbor->boats as $boat) {
                fputcsv($stream, [$boat->name, $boat->registration_no, $boat->boat_type, $boat->harbor_status]);
            }
            fputcsv($stream, []);
            fputcsv($stream, ['العامل', 'الفئة', 'الجنسية', 'الجوال', 'الحالة', 'البداية', 'النهاية']);
            foreach ($harbor->harborWorkers as $worker) {
                fputcsv($stream, [$worker->employee_name, $worker->worker_type, $worker->nationality, $worker->mobile_number, $worker->employment_status, $worker->start_date?->format('Y-m-d'), $worker->end_date?->format('Y-m-d')]);
            }
            fputcsv($stream, []);
            fputcsv($stream, ['الرخصة', 'النوع', 'صاحب الرخصة', 'رقم القارب', 'الإصدار', 'الانتهاء', 'الحالة']);
            foreach ($harbor->harborLicenses as $license) {
                fputcsv($stream, [$license->license_number, $license->license_type, $license->license_holder_name, $license->boat_number, $license->issue_date?->format('Y-m-d'), $license->expiry_date?->format('Y-m-d'), $license->license_status]);
            }
            fputcsv($stream, []);
            fputcsv($stream, ['المخالفة', 'النوع', 'التاريخ', 'القارب', 'المالك', 'الغرامة', 'الحالة', 'الوصف']);
            foreach ($harbor->harborViolations as $violation) {
                fputcsv($stream, [$violation->violation_number, $violation->violation_type, $violation->violation_date->format('Y-m-d H:i'), $violation->boat?->name, $violation->boat_owner_name, $violation->fine_amount, $violation->violation_status, $violation->violation_description]);
            }
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
