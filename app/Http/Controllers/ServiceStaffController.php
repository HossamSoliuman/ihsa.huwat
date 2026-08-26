<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\FisherServiceType;
use App\Models\Port;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * إدارة الموظفين — إسناد كل موظف لقسمه، وتخويله بالخدمات، وضبط صلاحياته،
 * ثم تتبّع ما أُسند إليه من طلبات ومهام.
 *
 * التخويل بالخدمات علاقة لا نص: تركه فارغًا يعني "كل الخدمات"، فتظل إضافة
 * خدمة جديدة لاحقًا داخلة في تخويل من لم يُقيَّد.
 */
class ServiceStaffController extends Controller
{
    public function index(Request $request): View
    {
        $staff = FisherServiceStaff::with(['serviceTypes', 'assignedPort', 'assignedRegion'])
            ->orderBy('name')
            ->get();

        $requests = FisherServiceRequest::with('serviceType')->get();
        $tasks = AdminTask::with('assignee')->get();

        $section = $request->query('section');
        $search = trim((string) $request->query('q'));

        $filtered = $staff
            ->when($request->filled('section'), fn ($rows) => $rows->where('section', $section))
            ->when($search !== '', fn ($rows) => $rows->filter(
                fn (FisherServiceStaff $row) => mb_stripos(
                    implode(' ', array_filter([$row->name, $row->job_number, $row->email, $row->role])),
                    $search
                ) !== false
            ))
            ->values();

        $sectionCounts = collect(FisherServiceStaff::SECTIONS)
            ->mapWithKeys(fn (string $name) => [$name => $staff->where('section', $name)->count()])
            ->all();

        return view('staff-management.index', [
            'staff' => $filtered,
            'section' => $section,
            'query' => $search,
            'sections' => FisherServiceStaff::SECTIONS,
            'sectionCounts' => $sectionCounts,
            'roles' => FisherServiceStaff::ROLES,
            'permissionFields' => FisherServiceStaff::PERMISSION_FIELDS,
            'serviceTypes' => FisherServiceType::orderBy('display_order')->orderBy('id')->get(),
            'ports' => Port::orderBy('name')->get(),
            'regions' => Region::orderBy('name')->get(),
            // الطلبات والمهام مجمّعة مسبقًا: كل بطاقة موظف تقرأ عدّها من هنا
            // بدل استعلام لكل بطاقة.
            'requestsByStaff' => $requests->groupBy('assigned_staff_id'),
            'tasksByStaff' => $tasks->groupBy(fn (AdminTask $task) => $task->assignee?->name),
            'stats' => [
                'total' => $staff->count(),
                'active' => $staff->where('active', true)->count(),
                'openTasks' => $tasks->whereNotIn('status', AdminTask::CLOSED)->count(),
                'completedTasks' => $tasks->where('status', 'مكتملة')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $types = $this->serviceTypeIds($request);

        $staff = FisherServiceStaff::create($data);
        $staff->serviceTypes()->sync($types);

        return $this->back($request, 'تمت إضافة الموظف');
    }

    public function update(Request $request, FisherServiceStaff $staff): RedirectResponse
    {
        $data = $this->validated($request, $staff);
        $types = $this->serviceTypeIds($request);

        $staff->update($data);
        $staff->serviceTypes()->sync($types);

        return $this->back($request, 'تم تحديث بيانات الموظف');
    }

    /**
     * نقل الموظف بين الأقسام — إجراء مستقل لأنه يُنفَّذ من بطاقة الموظف مباشرة.
     */
    public function reassign(Request $request, FisherServiceStaff $staff): RedirectResponse
    {
        $data = $request->validate([
            'section' => ['required', 'in:'.implode(',', FisherServiceStaff::SECTIONS)],
        ]);

        $staff->update($data);

        return $this->back($request, 'تم نقل '.$staff->name.' إلى قسم '.$data['section']);
    }

    public function destroy(Request $request, FisherServiceStaff $staff): RedirectResponse
    {
        // موظف بطلبات مسندة لا يُحذف: الحذف يقطع سجل من عالج ماذا. يوقَف بدلًا
        // من ذلك، فيختفي من قوائم الإسناد ويبقى أثره.
        if ($staff->requests()->exists()) {
            throw ValidationException::withMessages([
                'staff' => 'للموظف طلبات مسندة — أوقفه بدل حذفه ليبقى سجل المعالجة كاملًا.',
            ]);
        }

        $staff->delete();

        return $this->back($request, 'تم حذف الموظف');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?FisherServiceStaff $staff = null): array
    {
        $unique = 'unique:fisher_service_staff,job_number'.($staff !== null ? ','.$staff->id : '');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_number' => ['nullable', 'string', 'max:50', $unique],
            'email' => ['nullable', 'email', 'max:255'],
            'role' => ['required', 'in:'.implode(',', FisherServiceStaff::ROLES)],
            'section' => ['required', 'in:'.implode(',', FisherServiceStaff::SECTIONS)],
            'assigned_port_id' => ['nullable', 'exists:ports,id'],
            'assigned_region_id' => ['nullable', 'exists:regions,id'],
            'active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach (FisherServiceStaff::PERMISSION_FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        $data['active'] = $request->boolean('active');

        return $data;
    }

    /**
     * معرّفات الخدمات المخوّل بها — "الكل" تُحفظ بلا سطور، فلا تُقيَّد بها لاحقًا.
     *
     * @return array<int, int>
     */
    private function serviceTypeIds(Request $request): array
    {
        if ($request->boolean('all_services')) {
            return [];
        }

        $data = $request->validate([
            'service_type_ids' => ['nullable', 'array'],
            'service_type_ids.*' => ['integer', 'exists:fisher_service_types,id'],
        ]);

        return array_map('intval', $data['service_type_ids'] ?? []);
    }

    private function back(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('services.staff-management', $request->only('section', 'q'))
            ->with('status', $message);
    }
}
