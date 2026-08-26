<?php

namespace App\Http\Controllers;

use App\Models\AdminTask;
use App\Models\Alert;
use App\Models\FisherServiceRequest;
use App\Models\FisherServiceStaff;
use App\Models\StaffNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * مساحتي — واجهة الموظف الواحد: ما يلزمه الآن، وما أنجزه، وما وصله.
 *
 * تختلف عن "لوحة الموظف" في القصد: تلك تعرض قائمة موظف من الخارج للمتابعة،
 * وهذه تجمع لصاحبها إجراءاته الفورية وتقرير أدائه ومهامه وتنبيهاته وإشعاراته
 * في مكان واحد.
 *
 * البوابة بلا مصادقة بعد، فتُحدَّد الهوية من قائمة صريحة بدل الجلسة.
 */
class MyWorkspaceController extends Controller
{
    /** نافذة تقرير الأداء بالأيام — "الكل" حين لا تُحدَّد. */
    public const PERIODS = ['month' => 'هذا الشهر', 'q90' => 'آخر ٩٠ يومًا', 'all' => 'الكل'];

    public function index(Request $request): View
    {
        $staff = FisherServiceStaff::with(['serviceTypes', 'assignedPort', 'assignedRegion'])
            ->where('active', true)
            ->orderBy('name')
            ->get();

        $me = $staff->firstWhere('id', (int) $request->query('staff')) ?? $staff->first();

        $requests = FisherServiceRequest::with(FisherServiceRequest::DISPLAY_RELATIONS)
            ->orderByDesc('submitted_date')
            ->orderByDesc('id')
            ->get();

        $approval = collect();
        $processing = collect();

        if ($me !== null) {
            $mine = $requests->filter(fn (FisherServiceRequest $row) => $me->handles($row));

            if ($me->can_approve) {
                $approval = $mine->where('status', 'بانتظار الاعتماد')->values();
            }

            if ($me->can_process) {
                $processing = $mine
                    ->whereIn('status', FisherServiceRequest::OPEN)
                    ->filter(fn (FisherServiceRequest $row) => $row->assigned_staff_id === null || $row->assigned_staff_id === $me->id)
                    ->values();
            }
        }

        $tasks = $me === null
            ? collect()
            : AdminTask::with(['position', 'assignee'])
                ->whereHas('assignee', fn ($query) => $query->where('name', $me->name))
                ->orderBy('due_date')
                ->get();

        $alerts = Alert::whereIn('status', ['جديدة', 'قيد المعالجة'])->orderByDesc('date')->get();

        // التنبيه يصل بالاسم أو بالبريد — الشرطان في مجموعة واحدة كي لا يبتلع
        // "أو" بقية القيود لو أُضيفت لاحقًا.
        $notifications = $me === null
            ? collect()
            : StaffNotification::where(function ($query) use ($me) {
                $query->where('recipient_name', $me->name);

                if ($me->email !== null) {
                    $query->orWhere('recipient_email', $me->email);
                }
            })->latest('created_at')->get();

        $period = array_key_exists((string) $request->query('period'), self::PERIODS)
            ? (string) $request->query('period')
            : 'q90';

        return view('my-workspace.index', [
            'staff' => $staff,
            'me' => $me,
            'approval' => $approval,
            'processing' => $processing,
            'tasks' => $tasks,
            'urgentTasks' => $tasks->filter(fn (AdminTask $task) => $task->priority === 'عاجلة' || $task->isOverdue())->values(),
            'alerts' => $alerts,
            'criticalAlerts' => $alerts->whereIn('severity', ['حرج', 'مرتفع'])->values(),
            'notifications' => $notifications,
            'unread' => $notifications->where('read', false)->count(),
            'permissionFields' => FisherServiceStaff::PERMISSION_FIELDS,
            'processingStatuses' => FisherServiceRequest::PROCESSING_STATUSES,
            'periods' => self::PERIODS,
            'period' => $period,
            'performance' => $this->performance($me, $requests, $tasks, $period),
        ]);
    }

    public function markRead(Request $request, StaffNotification $notification): RedirectResponse
    {
        $notification->update(['read' => true, 'read_at' => now()]);

        return redirect()
            ->route('services.my-workspace', $request->only('staff', 'period'))
            ->with('status', 'تم تعليم التنبيه كمقروء');
    }

    /**
     * تقرير الأداء — إنتاجية وجودة والتزام داخل النافذة المختارة.
     *
     * المعيار في كل رقم "ما فعله هذا الموظف"، لا "ما مرّ عليه": الطلب يُحسب
     * لمن أُسند إليه، والقرار لمن وقّعه، فلا يُنسب عمل إلى غير صاحبه.
     *
     * @return array<string, mixed>
     */
    private function performance(?FisherServiceStaff $me, Collection $requests, Collection $tasks, string $period): array
    {
        $cutoff = match ($period) {
            'month' => Carbon::today()->startOfMonth(),
            'q90' => Carbon::today()->subDays(90),
            default => null,
        };

        if ($me === null) {
            return ['decisions' => 0, 'processed' => 0, 'approved' => 0, 'rejected' => 0, 'approvedRatio' => 0,
                'rejectRate' => 0, 'returned' => 0, 'tasksCompleted' => 0, 'overdue' => 0, 'onTimeRate' => 0,
                'onTime' => 0, 'closedTasks' => 0, 'avgDays' => null];
        }

        $inPeriod = fn (?Carbon $date) => $cutoff === null || $date === null || $date->gte($cutoff);

        $window = $requests->filter(fn (FisherServiceRequest $row) => $inPeriod($row->submitted_date));
        $assigned = $window->where('assigned_staff_id', $me->id);
        $signed = $window->where('approved_by', $me->name);

        // القرار: ما وقّعه بالاعتماد، وما رُفض من طلبات كانت مسندة إليه.
        $decisions = $signed->merge($assigned->where('status', 'مرفوضة'))->unique('id');
        $approved = $decisions->where('status', 'معتمدة');
        $rejected = $decisions->where('status', 'مرفوضة');

        $myTasks = $tasks->filter(fn (AdminTask $task) => $inPeriod($task->due_date));
        $closed = $myTasks->where('status', 'مكتملة')->filter(fn (AdminTask $task) => $task->completed_at !== null);
        $onTime = $closed->filter(fn (AdminTask $task) => $task->due_date !== null && $task->completed_at->lte($task->due_date->copy()->endOfDay()));

        $durations = $approved
            ->filter(fn (FisherServiceRequest $row) => $row->submitted_date !== null && $row->approved_at !== null)
            ->map(fn (FisherServiceRequest $row) => $row->submitted_date->diffInDays($row->approved_at));

        return [
            'decisions' => $decisions->count(),
            'processed' => $assigned->count(),
            'approved' => $approved->count(),
            'rejected' => $rejected->count(),
            'approvedRatio' => $decisions->count() > 0 ? (int) round($approved->count() / $decisions->count() * 100) : 0,
            'rejectRate' => $decisions->count() > 0 ? (int) round($rejected->count() / $decisions->count() * 100) : 0,
            'returned' => $assigned->where('status', 'بحاجة مستندات')->count(),
            'tasksCompleted' => $myTasks->where('status', 'مكتملة')->count(),
            'overdue' => $myTasks->filter(fn (AdminTask $task) => $task->isOverdue())->count(),
            'onTime' => $onTime->count(),
            'closedTasks' => $closed->count(),
            'onTimeRate' => $closed->count() > 0 ? (int) round($onTime->count() / $closed->count() * 100) : 0,
            'avgDays' => $durations->isEmpty() ? null : round($durations->avg(), 1),
        ];
    }
}
