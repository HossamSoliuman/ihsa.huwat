<?php

namespace App\Http\Controllers;

use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\Trip;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * لوحة الموظف — ما ينتظر موظفًا بعينه، مقيسًا بصلاحياته ونطاقه الجغرافي.
 *
 * القوائم لا تُبنى من الحالة وحدها: الطلب يظهر لمن يملك الصلاحية المناسبة
 * (اعتماد أو معالجة) وتقع خدمته وميناؤه داخل تخويله. ولذلك موظف بلا صلاحية
 * يرى لوحة فارغة لا لوحة كاملة معطّلة الأزرار.
 *
 * البوابة بلا مصادقة بعد، فيُختار الموظف من قائمة بدل أن يُقرأ من الجلسة.
 */
class ServiceStaffDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $staff = FisherServiceStaff::with(['serviceTypes', 'assignedPort.governorate.region', 'assignedRegion'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $selected = $staff->firstWhere('id', (int) $request->query('staff')) ?? $staff->first();

        $requests = FisherServiceRequest::with(FisherServiceRequest::DISPLAY_RELATIONS)
            ->orderByDesc('submitted_date')
            ->orderByDesc('id')
            ->get();

        $approval = collect();
        $processing = collect();

        if ($selected !== null) {
            $inScope = $requests->filter(fn (FisherServiceRequest $row) => $selected->handles($row));

            if ($selected->can_approve) {
                $approval = $inScope->where('status', 'بانتظار الاعتماد')->values();
            }

            if ($selected->can_process) {
                $processing = $inScope
                    ->whereIn('status', FisherServiceRequest::OPEN)
                    // الطلب غير المسند مفتوح للجميع، والمسند لا يظهر إلا لصاحبه.
                    ->filter(fn (FisherServiceRequest $row) => $row->assigned_staff_id === null || $row->assigned_staff_id === $selected->id)
                    ->values();
            }
        }

        return view('staff-dashboard.index', [
            'staff' => $staff,
            'selected' => $selected,
            'approval' => $approval,
            'processing' => $processing,
            'trips' => $this->scopedTrips($selected),
            'permissionFields' => FisherServiceStaff::PERMISSION_FIELDS,
            'processingStatuses' => FisherServiceRequest::PROCESSING_STATUSES,
        ]);
    }

    /**
     * رحلات نطاق الموظف — الميناء أخصّ من المنطقة، وبلا نطاق تُعرض المملكة.
     */
    private function scopedTrips(?FisherServiceStaff $staff)
    {
        $trips = Trip::with(['boat', 'departurePort.governorate.region'])->orderByDesc('id')->limit(200)->get();

        if ($staff !== null && $staff->assigned_port_id !== null) {
            $trips = $trips->where('departure_port_id', $staff->assigned_port_id);
        } elseif ($staff !== null && $staff->assigned_region_id !== null) {
            $trips = $trips->filter(
                fn (Trip $trip) => $trip->departurePort?->governorate?->region_id === $staff->assigned_region_id
            );
        }

        return $trips->take(12)->values();
    }
}
