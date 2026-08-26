<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\FisherServiceType;
use App\Models\FishingSeason;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * خدمات الصيادين — استقبال الطلب ومعالجته واعتماده وطباعة رخصته.
 *
 * الخطوتان مفصولتان عمدًا: المعالج ينقل الطلب في مساره ويقترح رقم الرخصة،
 * والمعتمِد وحده يُصدرها بتوقيعه. لذلك لا تظهر "معتمدة" بين حالات المعالجة،
 * ولا يجري الاعتماد إلا على طلب بلغ "بانتظار الاعتماد".
 */
class FisherServiceRequestController extends Controller
{
    public function index(Request $request): View
    {
        $requests = FisherServiceRequest::with(FisherServiceRequest::DISPLAY_RELATIONS)
            ->orderByDesc('submitted_date')
            ->orderByDesc('id')
            ->get();

        $search = trim((string) $request->query('q'));
        $status = $request->query('status');
        $type = $request->query('type');

        $filtered = $requests
            ->when($search !== '', fn ($rows) => $rows->filter(fn (FisherServiceRequest $row) => self::matches($row, $search)))
            ->when($request->filled('status'), fn ($rows) => $rows->where('status', $status))
            ->when($request->filled('type'), fn ($rows) => $rows->where('fisher_service_type_id', (int) $type))
            ->values();

        return view('fisher-services.index', [
            'requests' => $filtered,
            'query' => $search,
            'types' => FisherServiceType::where('active', true)->orderBy('display_order')->orderBy('id')->get(),
            'statuses' => FisherServiceRequest::STATUSES,
            'processingStatuses' => FisherServiceRequest::PROCESSING_STATUSES,
            'priorities' => FisherServiceRequest::PRIORITIES,
            'nationalityTypes' => FisherServiceRequest::NATIONALITY_TYPES,
            'nextNumber' => FisherServiceRequest::nextNumber(),
            'fishers' => Fisher::with('port')->orderBy('name')->get(),
            'ports' => Port::orderBy('name')->get(),
            'boats' => Boat::orderBy('name')->get(),
            'seasons' => FishingSeason::orderBy('name')->get(),
            'staff' => FisherServiceStaff::where('active', true)->orderBy('name')->get(),
            'stats' => [
                'total' => $requests->count(),
                'new' => $requests->where('status', 'جديدة')->count(),
                'inProgress' => $requests->whereIn('status', ['قيد المعالجة', 'بحاجة مستندات'])->count(),
                'approval' => $requests->where('status', 'بانتظار الاعتماد')->count(),
                'approved' => $requests->where('status', 'معتمدة')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'fisher_service_type_id' => ['required', 'exists:fisher_service_types,id'],
            'fisher_id' => ['nullable', 'exists:fishers,id'],
            'fisher_name' => ['required', 'string', 'max:255'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'string', 'max:10'],
            'birth_date' => ['nullable', 'date'],
            'nationality_type' => ['required', 'in:'.implode(',', FisherServiceRequest::NATIONALITY_TYPES)],
            'nationality' => ['nullable', 'string', 'max:100'],
            'profession' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:255'],
            'port_id' => ['nullable', 'exists:ports,id'],
            'center' => ['nullable', 'string', 'max:255'],
            'boat_id' => ['nullable', 'exists:boats,id'],
            'fishing_season_id' => ['nullable', 'exists:fishing_seasons,id'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', 'in:'.implode(',', FisherServiceRequest::PRIORITIES)],
            'description' => ['nullable', 'string'],
        ]);

        $type = FisherServiceType::findOrFail($data['fisher_service_type_id']);

        // خدمة موسمية بلا موسم تُصدر رخصة بلا نافذة زمنية — يُرَدّ النموذج
        // بدل حفظ طلب ناقص يتعثّر عند الاعتماد.
        if ($type->requires_season && ($data['fishing_season_id'] ?? null) === null) {
            throw ValidationException::withMessages([
                'fishing_season_id' => 'خدمة «'.$type->name.'» تحتاج موسم صيد مرتبطًا.',
            ]);
        }

        // الجنسية السعودية لا تُكتب يدويًا، فلا تتناقض مع نوعها.
        $data['nationality'] = $data['nationality_type'] === 'سعودي' ? 'سعودي' : ($data['nationality'] ?? null);

        FisherServiceRequest::create($data + [
            'request_number' => FisherServiceRequest::nextNumber(),
            'status' => 'جديدة',
            'submitted_date' => now()->toDateString(),
        ]);

        return $this->back($request, 'تم تقديم الطلب');
    }

    /**
     * معالجة الطلب — نقله في مساره وإسناده ومقترح الرخصة.
     */
    public function process(Request $request, FisherServiceRequest $serviceRequest): RedirectResponse
    {
        $this->refuseClosed($serviceRequest);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', FisherServiceRequest::PROCESSING_STATUSES)],
            'assigned_staff_id' => ['nullable', 'exists:fisher_service_staff,id'],
            'new_license_number' => ['nullable', 'string', 'max:100'],
            'new_license_expiry' => ['nullable', 'date'],
            'resolution' => ['nullable', 'string'],
        ]);

        // من يعالج الطلب يلزم أن يملك صلاحية المعالجة وأن يقع الطلب في نطاقه —
        // وإلا حُفظ إسناد لا يستطيع صاحبه تنفيذه.
        if (($data['assigned_staff_id'] ?? null) !== null) {
            $staff = FisherServiceStaff::with('serviceTypes')->find($data['assigned_staff_id']);

            if ($staff === null || ! $staff->holds('معالجة')) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => 'الموظف المحدد لا يملك صلاحية المعالجة.',
                ]);
            }

            if (! $staff->handles($serviceRequest->loadMissing('port.governorate'))) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => 'الطلب خارج تخويل الموظف أو نطاقه الجغرافي.',
                ]);
            }
        }

        if ($data['status'] === 'بانتظار الاعتماد') {
            $data['processed_date'] = now()->toDateString();
        }

        $serviceRequest->update($data);

        return $this->back($request, 'تم تحديث الطلب');
    }

    /**
     * قرار الاعتماد — إصدار الرخصة بتوقيع المسؤول، أو الرفض.
     */
    public function decide(Request $request, FisherServiceRequest $serviceRequest): RedirectResponse
    {
        $this->refuseClosed($serviceRequest);

        if ($serviceRequest->status !== 'بانتظار الاعتماد') {
            throw ValidationException::withMessages([
                'decision' => 'لا يُعتمد الطلب قبل أن تنتهي معالجته.',
            ]);
        }

        $data = $request->validate([
            'decision' => ['required', 'in:اعتماد,رفض'],
            'approved_by' => ['required_if:decision,اعتماد', 'nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        $note = trim((string) ($data['note'] ?? ''));

        if ($data['decision'] === 'اعتماد') {
            $serviceRequest->update([
                'status' => 'معتمدة',
                'approved_by' => $data['approved_by'],
                'approved_at' => now(),
                'resolution' => 'تم الاعتماد بواسطة '.$data['approved_by'].($note !== '' ? ' — '.$note : ''),
            ]);

            return $this->back($request, 'تم اعتماد الطلب وإصدار الرخصة');
        }

        $serviceRequest->update([
            'status' => 'مرفوضة',
            'resolution' => 'رفض الاعتماد'
                .($data['approved_by'] ? ' بواسطة '.$data['approved_by'] : '')
                .($note !== '' ? ' — '.$note : ''),
        ]);

        return $this->back($request, 'تم رفض الطلب');
    }

    /**
     * بطاقة الرخصة الصادرة — صفحة طباعة مستقلة عن تخطيط اللوحة.
     */
    public function license(FisherServiceRequest $serviceRequest): View
    {
        abort_unless($serviceRequest->status === 'معتمدة', 404);

        return view('fisher-services.license', [
            'request' => $serviceRequest->load(FisherServiceRequest::DISPLAY_RELATIONS),
        ]);
    }

    /**
     * البحث يطابق ما يعرفه الموظف عن الطلب: الاسم والهوية والرقمين والميناء.
     */
    private static function matches(FisherServiceRequest $row, string $needle): bool
    {
        $haystack = implode(' ', array_filter([
            $row->fisher_name,
            $row->national_id,
            $row->license_number,
            $row->request_number,
            $row->port?->name,
        ]));

        return mb_stripos($haystack, $needle) !== false;
    }

    private function refuseClosed(FisherServiceRequest $serviceRequest): void
    {
        if (in_array($serviceRequest->status, FisherServiceRequest::CLOSED, true)) {
            throw ValidationException::withMessages([
                'status' => 'الطلب مغلق — لا يُعاد فتحه بعد الاعتماد أو الرفض.',
            ]);
        }
    }

    private function back(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('services.fisher-services', $request->only('q', 'status', 'type'))
            ->with('status', $message);
    }
}
