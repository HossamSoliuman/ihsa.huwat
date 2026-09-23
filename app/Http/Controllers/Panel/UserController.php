<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Port;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * حسابات التطبيق — يديرها المدير العام وحده.
 *
 * ينشئ الملاك والعدّادين والدلالين والتجار والمديرين، أمّا الكابتن والطاقم
 * والموظف فينشئهم المالك من بوابته (Role::OWNER_MANAGED) ولا يُختارون هنا إلا
 * مع مالك يتبعونه. حسابات الوزارة (بلا دور تطبيق) لا تظهر في هذه القائمة.
 *
 * العدّاد وحده يُسأل عن ميناء: طابوره يُحسب عليه، ويُحفظ في سجلّ موظف
 * الإحصاء (statistics_officers) لا في جدول المستخدمين — هو سجلّ الوزارة نفسه
 * الذي تقرؤه صفحة موظفي الإحصاء.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $roles = Role::orderBy('display_order')->get();

        $query = User::with(['appRole', 'owner', 'statisticsOfficer'])
            ->whereNotNull('role_id')
            ->when($request->filled('role'), fn ($q) => $q->whereHas('appRole', fn ($r) => $r->where('key', $request->query('role'))))
            ->when($request->query('status') === 'active', fn ($q) => $q->where('active', true))
            ->when($request->query('status') === 'inactive', fn ($q) => $q->where('active', false))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $phone = User::normalizePhone($request->query('search'));

                $q->where(fn ($w) => $w->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->when($phone, fn ($p) => $p->orWhere('phone', 'like', '%'.$phone.'%')));
            })
            ->orderByDesc('id');

        return view('panel.users.index', [
            'users' => $query->paginate(25)->withQueryString(),
            'roles' => $roles,
            'owners' => User::whereHas('appRole', fn ($r) => $r->where('key', Role::OWNER))->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'counts' => [
                'total' => User::whereNotNull('role_id')->count(),
                'active' => User::whereNotNull('role_id')->where('active', true)->count(),
                'owners' => User::whereHas('appRole', fn ($r) => $r->where('key', Role::OWNER))->count(),
                'dalals' => User::whereHas('appRole', fn ($r) => $r->where('key', Role::DALAL))->count(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $portId = $this->pullPortId($data);

        $user = User::create($data);
        $this->syncCounterRecord($user, $portId);

        $this->log('إنشاء', $user);

        return redirect()->route('panel.users')->with('status', 'تم إنشاء الحساب.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role_id === null, 404);

        $data = $this->validated($request, $user);
        $portId = $this->pullPortId($data);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);
        $this->syncCounterRecord($user->refresh(), $portId);

        $this->log('تحديث', $user);

        return redirect()->route('panel.users')->with('status', 'تم تحديث الحساب.');
    }

    /**
     * تعطيل الحساب بدل حذفه: رحلاته ومبيعاته تبقى منسوبة إليه. الحساب المعطّل
     * لا يدخل اللوحة ولا التطبيق وتُلغى رموزه.
     */
    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role_id === null, 404);

        if ($user->is($request->user())) {
            return redirect()->route('panel.users')->withErrors(['toggle' => 'لا يمكنك تعطيل حسابك أنت.']);
        }

        $user->update(['active' => ! $user->active]);

        if (! $user->active) {
            $user->tokens()->delete();
        }

        $this->log($user->active ? 'تفعيل' : 'تعطيل', $user);

        return redirect()->route('panel.users')->with('status', $user->active ? 'تم تفعيل الحساب.' : 'تم تعطيل الحساب.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->role_id === null, 404);

        if ($user->is($request->user())) {
            return redirect()->route('panel.users')->withErrors(['toggle' => 'لا يمكنك حذف حسابك أنت.']);
        }

        $this->log('حذف', $user);

        $user->tokens()->delete();
        $user->delete();

        return redirect()->route('panel.users')->with('status', 'تم حذف الحساب.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        // الجوال يُطبَّع قبل التحقق حتى يُطابق قيد الفرادة الصيغة المخزّنة.
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/', Rule::unique('users', 'phone')->ignore($user)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('active', true)],
            'owner_id' => ['nullable', Rule::exists('users', 'id')],
            'port_id' => ['nullable', Rule::exists('ports', 'id')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'active' => ['nullable', 'boolean'],
        ], [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
        ]);

        $role = Role::findOrFail($data['role_id']);

        // من يتبع مالكًا لا بدّ له من مالك؛ ومن لا يتبع أحدًا لا يُربط بأحد.
        if (in_array($role->key, Role::OWNER_MANAGED, true)) {
            $request->validate(['owner_id' => ['required']], ['owner_id.required' => 'هذا الدور يتبع مالكًا — اختر المالك.']);
            $owner = User::findOrFail($data['owner_id']);
            abort_unless($owner->hasAppRole(Role::OWNER), 422, 'الحساب المختار ليس مالكًا.');
        } else {
            $data['owner_id'] = null;
        }

        // العدّاد لا بدّ له من ميناء يعمل فيه؛ وغيره لا ميناء له.
        if ($role->key === Role::COUNTER) {
            $request->validate(['port_id' => ['required']], ['port_id.required' => 'العدّاد يعمل في ميناء — اختر الميناء.']);
        } else {
            $data['port_id'] = null;
        }

        $data['email'] = $data['email'] ?? null;
        $data['active'] = $request->boolean('active', true);

        return $data;
    }

    /**
     * الميناء ليس عمودًا في users — يُنزع من بياناته ويُحفظ في سجلّ الموظف.
     */
    private function pullPortId(array &$data): ?int
    {
        $portId = $data['port_id'] ?? null;
        unset($data['port_id']);

        return $portId === null ? null : (int) $portId;
    }

    /**
     * سجلّ موظف الإحصاء لحساب العدّاد: يُنشأ بميناء الحساب أو يُحدَّث به،
     * ويُفكّ الربط إن لم يعد الحساب عدّادًا (السجلّ يبقى — هو سجلّ الوزارة).
     */
    private function syncCounterRecord(User $user, ?int $portId): void
    {
        if (! $user->hasAppRole(Role::COUNTER) || $portId === null) {
            StatisticsOfficer::forUser($user)->update(['user_id' => null]);

            return;
        }

        StatisticsOfficer::updateOrCreate(
            ['user_id' => $user->id],
            [
                'port_id' => $portId,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => $user->active ? 'نشط' : 'غير نشط',
            ] + (StatisticsOfficer::forUser($user)->exists() ? [] : ['employee_number' => StatisticsOfficer::numberFor($user)]),
        );
    }

    private function log(string $action, User $subject): void
    {
        AuditLog::create([
            'user_email' => request()->user()->email ?? request()->user()->phone,
            'role' => request()->user()->app_role_key ?? 'admin',
            'action' => $action,
            'entity' => 'User',
            'record_label' => $subject->name.' ('.$subject->phone.')',
            'details' => "{$action} حساب تطبيق بدور «{$subject->appRole?->name}»",
            'ip' => request()->ip(),
        ]);
    }
}
