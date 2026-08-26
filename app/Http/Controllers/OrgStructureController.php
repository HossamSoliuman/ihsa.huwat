<?php

namespace App\Http\Controllers;

use App\Models\OrgPosition;
use App\Models\OrgStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * الهيكل التنظيمي — المناصب شجرةً وجدولًا، وشاغلو كل منصب.
 */
class OrgStructureController extends Controller
{
    public const LEVELS = ['وكيل وزارة', 'مدير عام', 'مدير إدارة', 'مدير قسم', 'مسؤول', 'موظف مشرف'];

    public const ROLES = [
        'admin' => 'مدير النظام',
        'top_management' => 'الإدارة العليا',
        'fisheries_admin' => 'مدير الثروة السمكية',
        'region_manager' => 'مدير منطقة',
        'port_manager' => 'مدير ميناء',
        'supervision' => 'إشراف',
        'user' => 'مستخدم',
    ];

    public const SCOPES = [
        'kingdom' => 'المملكة',
        'region' => 'المنطقة',
        'governorate' => 'المحافظة',
        'port' => 'الميناء',
    ];

    public const RANKS = ['الرتبة الأولى', 'الرتبة الثانية', 'الرتبة الثالثة', 'الرتبة الرابعة', 'الرتبة الخامسة', 'تعاقد'];

    public const STAFF_STATUSES = ['نشط', 'إجازة', 'مكلف', 'متوقف'];

    public function index(Request $request): View
    {
        $positions = OrgPosition::with('staff')->orderBy('display_order')->orderBy('id')->get();

        $level = $request->query('level');
        $table = $positions->when($request->filled('level'), fn ($rows) => $rows->where('level', $level))->values();

        return view('org-structure.index', [
            'view' => $request->query('view') === 'table' ? 'table' : 'tree',
            'tree' => self::tree($positions),
            'positions' => $positions,
            'rows' => $table,
            'levels' => self::LEVELS,
            'roles' => self::ROLES,
            'scopes' => self::SCOPES,
            'ranks' => self::RANKS,
            'staffStatuses' => self::STAFF_STATUSES,
            'stats' => [
                'positions' => $positions->count(),
                'staff' => $positions->sum(fn (OrgPosition $position) => $position->staff->count()),
                'levels' => $positions->pluck('level')->unique()->count(),
                'vacant' => $positions->filter(fn (OrgPosition $position) => $position->staff->isEmpty())->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        OrgPosition::create($this->validated($request));

        return redirect()->route('subadmin.org-structure')->with('status', 'تمت إضافة المنصب');
    }

    public function update(Request $request, OrgPosition $position): RedirectResponse
    {
        $data = $this->validated($request);

        // منصب أبًا لنفسه يقطع الشجرة عن جذرها فتختفي فروعها من العرض.
        if ((int) ($data['parent_id'] ?? 0) === $position->id) {
            $data['parent_id'] = null;
        }

        $position->update($data);

        return redirect()->route('subadmin.org-structure')->with('status', 'تم تحديث المنصب');
    }

    public function destroy(OrgPosition $position): RedirectResponse
    {
        $position->delete();

        return redirect()->route('subadmin.org-structure')->with('status', 'تم حذف المنصب');
    }

    public function storeStaff(Request $request, OrgPosition $position): RedirectResponse
    {
        $position->staff()->create($this->validatedStaff($request));

        return redirect()->route('subadmin.org-structure')->with('status', 'تمت إضافة الموظف');
    }

    public function updateStaff(Request $request, OrgStaff $staff): RedirectResponse
    {
        $staff->update($this->validatedStaff($request));

        return redirect()->route('subadmin.org-structure')->with('status', 'تم تحديث بيانات الموظف');
    }

    public function destroyStaff(OrgStaff $staff): RedirectResponse
    {
        $staff->delete();

        return redirect()->route('subadmin.org-structure')->with('status', 'تم حذف الموظف');
    }

    /**
     * ترتيب المناصب شجرةً: كل منصب تحت أبيه، وما لا أب له (أو أبوه محذوف) جذر.
     *
     * @param  Collection<int, OrgPosition>  $positions
     * @return array<int, array{position: OrgPosition, depth: int}>
     */
    public static function tree(Collection $positions): array
    {
        $byParent = $positions->groupBy(fn (OrgPosition $position) => $position->parent_id ?? 0);
        $known = $positions->pluck('id')->all();

        $roots = $positions->filter(
            fn (OrgPosition $position) => $position->parent_id === null || ! in_array($position->parent_id, $known, true),
        );

        $flatten = function (Collection $nodes, int $depth) use (&$flatten, $byParent): array {
            $rows = [];

            foreach ($nodes as $node) {
                $rows[] = ['position' => $node, 'depth' => $depth];
                $rows = array_merge($rows, $flatten($byParent->get($node->id, collect()), $depth + 1));
            }

            return $rows;
        };

        return $flatten($roots, 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'in:'.implode(',', self::LEVELS)],
            'parent_id' => ['nullable', 'exists:org_positions,id'],
            'authorities' => ['nullable', 'string'],
            'responsibilities' => ['nullable', 'string'],
            'linked_role' => ['required', 'in:'.implode(',', array_keys(self::ROLES))],
            'scope_level' => ['required', 'in:'.implode(',', array_keys(self::SCOPES))],
            'reports_to' => ['nullable', 'string', 'max:255'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['display_order'] = $data['display_order'] ?? 0;
        $data['active'] = $request->boolean('active');

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedStaff(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_number' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'rank' => ['required', 'in:'.implode(',', self::RANKS)],
            'status' => ['required', 'in:'.implode(',', self::STAFF_STATUSES)],
            'start_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        foreach (OrgStaff::PERMISSION_FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        return $data;
    }
}
