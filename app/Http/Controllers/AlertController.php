<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\UserPermission;
use App\Support\AlertGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مركز الإنذارات — الإنذارات مرتّبة بالأولوية، وإسناد مسؤول لكل منها قبل إغلاقه.
 */
class AlertController extends Controller
{
    /** درجات الخطورة من الأعلى إلى الأدنى — هو ترتيب العرض نفسه. */
    public const SEVERITIES = ['حرج', 'مرتفع', 'متوسط', 'منخفض'];

    public const STATUSES = ['جديدة', 'قيد المعالجة', 'تم الحل'];

    public function index(Request $request): View
    {
        $alerts = Alert::orderByDesc('date')->get();

        $search = trim((string) $request->query('q'));

        $filtered = $alerts
            ->when($search !== '', fn ($rows) => $rows->filter(fn (Alert $alert) => str_contains(
                implode(' ', [$alert->title, $alert->description, $alert->boat, $alert->port, $alert->species, $alert->assigned_to]),
                $search,
            )))
            ->when($request->filled('severity'), fn ($rows) => $rows->where('severity', $request->query('severity')))
            ->when($request->filled('type'), fn ($rows) => $rows->where('type', $request->query('type')))
            ->when($request->filled('status'), fn ($rows) => $rows->where('status', $request->query('status')))
            ->values();

        $groups = collect(self::SEVERITIES)
            ->map(fn (string $severity) => ['severity' => $severity, 'items' => $filtered->where('severity', $severity)->values()])
            ->filter(fn (array $group) => $group['items']->isNotEmpty())
            ->values();

        return view('alerts.index', [
            'groups' => $groups,
            'severities' => self::SEVERITIES,
            'statuses' => self::STATUSES,
            'types' => $alerts->pluck('type')->unique()->filter()->values(),
            'people' => self::responsibles(),
            'stats' => [
                'total' => $alerts->count(),
                'critical' => $alerts->where('severity', 'حرج')->count(),
                'high' => $alerts->where('severity', 'مرتفع')->count(),
                'new' => $alerts->where('status', 'جديدة')->count(),
                'assigned' => $alerts->whereNotNull('assigned_to')->count(),
                'unassigned' => $alerts->whereNull('assigned_to')->where('status', '!=', 'تم الحل')->count(),
            ],
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $result = AlertGenerator::run();

        return $this->back($request, "تم توليد {$result['generated']} إنذارًا جديدًا وتحديث {$result['updated']}");
    }

    public function assign(Request $request, Alert $alert): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['required', 'string', 'max:255'],
        ]);

        // الإسناد يبدأ المعالجة: إنذار له مسؤول لم يعد "جديدًا" بلا متابع.
        $alert->update([
            'assigned_to' => $data['assigned_to'],
            'assigned_at' => now(),
            'status' => $alert->status === 'تم الحل' ? $alert->status : 'قيد المعالجة',
        ]);

        return $this->back($request, 'تم تعيين المسؤول: '.$data['assigned_to']);
    }

    public function resolve(Request $request, Alert $alert): RedirectResponse
    {
        // لا يُغلق إنذار بلا مسؤول: الإغلاق شهادة بأن أحدًا تابعه.
        if ($alert->assigned_to === null) {
            return $this->back($request, 'عيّن مسؤولًا للإنذار قبل إغلاقه', 'error');
        }

        $alert->update([
            'status' => 'تم الحل',
            'resolution_note' => $request->validate(['resolution_note' => ['nullable', 'string']])['resolution_note'] ?? null,
            'closed_at' => now(),
        ]);

        return $this->back($request, 'تم إغلاق الإنذار');
    }

    /**
     * المسؤولون الذين يجوز إسناد إنذار إليهم — المستخدمون النشطون وحدهم.
     *
     * @return array<int, string>
     */
    public static function responsibles(): array
    {
        return UserPermission::where('active', true)
            ->orderBy('user_email')
            ->get()
            ->map(fn (UserPermission $user) => $user->full_name ?: $user->user_email)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function back(Request $request, string $message, string $key = 'status'): RedirectResponse
    {
        return redirect()
            ->route('subadmin.alerts', $request->only('q', 'severity', 'type', 'status'))
            ->with($key, $message);
    }
}
