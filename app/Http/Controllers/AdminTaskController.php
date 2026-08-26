<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\OrgPosition;
use App\Models\OrgStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * تقويم المهام الإدارية — مهام كل قسم موزّعة على شهر، مع الصلاحية التي يلزم
 * توافرها في الموظف قبل إسناد المهمة إليه.
 */
class AdminTaskController extends Controller
{
    public const TYPES = ['مراجعة', 'اعتماد', 'إصدار رخصة', 'تقرير', 'اجتماع', 'متابعة', 'أخرى'];

    public const PRIORITIES = ['عادية', 'مهمة', 'عاجلة'];

    public const STATUSES = ['مجدولة', 'قيد التنفيذ', 'مكتملة', 'متأخرة', 'ملغاة'];

    public const RECURRENCES = ['بدون', 'يومي', 'أسبوعي', 'شهري', 'سنوي'];

    public const PERMISSIONS = ['إنشاء', 'معالجة', 'اعتماد', 'رفض', 'إسناد', 'أي صلاحية'];

    public const SECTIONS = ['الثروة السمكية', 'الإحصاء', 'الإدارة الفرعية', 'الخدمات والتراخيص'];

    /** القسم المختص الذي توجَّه إليه المهمة تلقائيًا بحسب نوعها. */
    private const SECTION_BY_TYPE = [
        'إصدار رخصة' => 'الخدمات والتراخيص',
        'تقرير' => 'الإحصاء',
        'مراجعة' => 'الإحصاء',
        'اعتماد' => 'الإدارة الفرعية',
        'اجتماع' => 'الإدارة الفرعية',
        'متابعة' => 'الإدارة الفرعية',
    ];

    public const WEEKDAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

    public const MONTHS = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

    public function index(Request $request): View
    {
        $cursor = self::cursor($request->query('month'));

        $tasks = AdminTask::with(['position', 'assignee'])->orderBy('due_date')->get();

        $position = $request->query('position');
        $status = $request->query('status');

        $filtered = $tasks
            ->when($request->filled('position'), fn ($rows) => $rows->where('org_position_id', (int) $position))
            ->when($request->filled('status'), fn ($rows) => $rows->where('status', $status))
            ->values();

        $day = $request->query('day');
        $byDate = $filtered->groupBy(fn (AdminTask $task) => $task->due_date->toDateString());

        return view('admin-tasks.index', [
            'cursor' => $cursor,
            'monthLabel' => self::MONTHS[$cursor->month - 1].' '.$cursor->year,
            'previous' => $cursor->copy()->subMonth()->format('Y-m'),
            'next' => $cursor->copy()->addMonth()->format('Y-m'),
            'weekdays' => self::WEEKDAYS,
            'cells' => self::cells($cursor),
            'byDate' => $byDate,
            'day' => $day,
            'dayTasks' => $day ? $byDate->get($day, collect()) : collect(),
            'monthTasks' => $filtered->filter(fn (AdminTask $task) => $task->due_date->isSameMonth($cursor))->values(),
            'positions' => OrgPosition::orderBy('display_order')->orderBy('id')->get(),
            'staff' => OrgStaff::with('position')->orderBy('name')->get(),
            'types' => self::TYPES,
            'priorities' => self::PRIORITIES,
            'statuses' => self::STATUSES,
            'recurrences' => self::RECURRENCES,
            'permissions' => self::PERMISSIONS,
            'permissionFields' => OrgStaff::PERMISSION_FIELDS,
            'today' => Carbon::today()->toDateString(),
            'stats' => [
                'total' => $filtered->count(),
                'scheduled' => $filtered->where('status', 'مجدولة')->count(),
                'inProgress' => $filtered->where('status', 'قيد التنفيذ')->count(),
                'overdue' => $filtered->filter(fn (AdminTask $task) => $task->isOverdue())->count(),
                'completed' => $filtered->where('status', 'مكتملة')->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        AdminTask::create($this->validated($request));

        return $this->back($request, 'تمت إضافة المهمة');
    }

    public function update(Request $request, AdminTask $task): RedirectResponse
    {
        $data = $this->validated($request);

        if ($data['status'] === 'مكتملة' && $task->completed_at === null) {
            $data['completed_at'] = now();
        }

        $task->update($data);

        return $this->back($request, 'تم تحديث المهمة');
    }

    public function complete(Request $request, AdminTask $task): RedirectResponse
    {
        $task->update([
            'status' => 'مكتملة',
            'completed_at' => now(),
            'completed_by' => $task->assignee?->name,
        ]);

        return $this->back($request, 'تم إنجاز المهمة');
    }

    public function destroy(Request $request, AdminTask $task): RedirectResponse
    {
        $task->delete();

        return $this->back($request, 'تم حذف المهمة');
    }

    /**
     * الشهر المعروض — يقبل YYYY-MM ويعود إلى الشهر الحالي عند غيابه أو فساده.
     */
    public static function cursor(?string $month): Carbon
    {
        if (is_string($month) && preg_match('/^\d{4}-\d{2}$/', $month) === 1) {
            $parsed = Carbon::createFromFormat('Y-m-d', $month.'-01');

            if ($parsed !== false) {
                return $parsed->startOfMonth();
            }
        }

        return Carbon::today()->startOfMonth();
    }

    /**
     * خانات شبكة الشهر: فراغات ما قبل أول يوم (الأحد أول الأسبوع) ثم أيامه.
     *
     * @return array<int, ?string>
     */
    public static function cells(Carbon $cursor): array
    {
        $cells = array_fill(0, $cursor->dayOfWeek, null);

        foreach (range(1, $cursor->daysInMonth) as $day) {
            $cells[] = $cursor->copy()->day($day)->toDateString();
        }

        return $cells;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'org_position_id' => ['required', 'exists:org_positions,id'],
            'assigned_staff_id' => ['nullable', 'exists:org_staff,id'],
            'required_permission' => ['required', 'in:'.implode(',', self::PERMISSIONS)],
            'task_type' => ['required', 'in:'.implode(',', self::TYPES)],
            'priority' => ['required', 'in:'.implode(',', self::PRIORITIES)],
            'status' => ['required', 'in:'.implode(',', self::STATUSES)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['required', 'date'],
            'recurrence' => ['required', 'in:'.implode(',', self::RECURRENCES)],
            'notes' => ['nullable', 'string'],
        ]);

        // القسم المختص يُشتق من نوع المهمة، فلا يُدخل يدويًا ولا يتناقض معه.
        $data['section'] = self::SECTION_BY_TYPE[$data['task_type']] ?? 'الإدارة الفرعية';

        // موظف بلا الصلاحية المطلوبة لا يُسند إليه العمل — يُرَدّ النموذج ولا
        // يُحذف الاختيار بصمت، وإلا حُفظت مهمة بلا مسؤول دون أن يدري المُدخل.
        if (($data['assigned_staff_id'] ?? null) !== null) {
            $staff = OrgStaff::find($data['assigned_staff_id']);

            if ($staff === null || ! $staff->holds($data['required_permission'])) {
                throw ValidationException::withMessages([
                    'assigned_staff_id' => 'الموظف المحدد لا يملك صلاحية «'.$data['required_permission'].'».',
                ]);
            }
        }

        return $data;
    }

    private function back(Request $request, string $message): RedirectResponse
    {
        return redirect()
            ->route('subadmin.admin-tasks', $request->only('month', 'position', 'status'))
            ->with('status', $message);
    }
}
