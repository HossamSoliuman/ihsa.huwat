<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\Role;
use App\Models\StatisticsOfficer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * شركات التشغيل — يديرها المدير العام: ينشئ الشركة ويسند إليها موانئها
 * (ميناء واحد لشركة واحدة) وينشئ حسابات موظفيها، ثم توظّف هي العدّادين
 * من بوابتها. إيقاف الشركة يغلق بوابتها ولا يمسّ عدّاديها العاملين.
 */
class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $companies = OperatingCompany::query()
            ->withCount([
                'ports',
                'staff',
                'counters',
                'applications as pending_count' => fn ($q) => $q->verified()->where('status', CounterApplication::PENDING),
            ])
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('commercial_register', 'like', $term));
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('panel.companies.index', [
            'companies' => $companies,
            'counts' => [
                'companies' => OperatingCompany::count(),
                'active' => OperatingCompany::active()->count(),
                'ports' => DB::table('operating_company_ports')->count(),
                'counters' => StatisticsOfficer::whereNotNull('operating_company_id')->count(),
            ],
        ]);
    }

    public function show(OperatingCompany $company): View
    {
        $company->load(['ports.governorate', 'staff.appRole']);

        return view('panel.companies.show', [
            'company' => $company,
            'freePorts' => Port::whereNotIn('id', DB::table('operating_company_ports')->select('port_id'))->orderBy('name')->get(['id', 'name']),
            'counters' => $company->counters()->with(['port', 'user'])->orderBy('name')->get(),
            'rounds' => $company->hiringRounds()->with('port')->withCount(['applications as approved_count' => fn ($q) => $q->where('status', CounterApplication::APPROVED)])->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = OperatingCompany::create($this->validated($request));
        $this->log('إنشاء', $company);

        return redirect()->route('panel.companies.show', $company)->with('status', 'تم إنشاء الشركة — أسند إليها موانئها وأنشئ حساب موظفها.');
    }

    public function update(Request $request, OperatingCompany $company): RedirectResponse
    {
        $company->update($this->validated($request, $company));
        $this->log('تحديث', $company);

        return back()->with('status', 'تم تحديث بيانات الشركة.');
    }

    /**
     * الحذف لشركة لم توظّف أحدًا بعد؛ غيرها يُوقف، فعدّادوها ورحلاتهم تبقى منسوبة إليها.
     */
    public function destroy(OperatingCompany $company): RedirectResponse
    {
        if ($company->counters()->exists() || $company->applications()->exists()) {
            return back()->withErrors(['company' => 'للشركة عدّادون أو طلبات توظيف — أوقفها بدل حذفها.']);
        }

        $this->log('حذف', $company);
        $company->staff()->each(fn (User $user) => $user->tokens()->delete());
        $company->staff()->update(['active' => false, 'operating_company_id' => null]);
        $company->delete();

        return redirect()->route('panel.companies')->with('status', 'تم حذف الشركة.');
    }

    public function attachPort(Request $request, OperatingCompany $company): RedirectResponse
    {
        $data = $request->validate([
            'port_id' => ['required', Rule::exists('ports', 'id'), Rule::unique('operating_company_ports', 'port_id')],
            'started_at' => ['nullable', 'date'],
        ], ['port_id.unique' => 'هذا الميناء تشغّله شركة أخرى.']);

        $company->ports()->attach($data['port_id'], ['started_at' => $data['started_at'] ?? today()]);
        $this->log('إسناد ميناء', $company, Port::find($data['port_id'])?->name);

        return back()->with('status', 'أُسند الميناء إلى الشركة.');
    }

    /**
     * لا يُنزع ميناء يعمل فيه عدّادو الشركة أو فيه جولة توظيف لم تُغلق.
     */
    public function detachPort(OperatingCompany $company, Port $port): RedirectResponse
    {
        abort_unless($company->operatesPort($port->id), 404);

        if ($company->counters()->where('port_id', $port->id)->exists()) {
            return back()->withErrors(['port' => "لعدّادي الشركة عمل في {$port->name} — انقلهم أولًا."]);
        }

        if ($company->hiringRounds()->where('port_id', $port->id)->where('status', '!=', HiringRound::CLOSED)->exists()) {
            return back()->withErrors(['port' => "في {$port->name} جولة توظيف لم تُغلق."]);
        }

        $company->ports()->detach($port->id);
        $this->log('نزع ميناء', $company, $port->name);

        return back()->with('status', 'نُزع الميناء من الشركة.');
    }

    /**
     * حساب موظف للشركة يدخل بوابتها — كحساب من صفحة الحسابات بدور الشركة.
     */
    public function storeStaff(Request $request, OperatingCompany $company): RedirectResponse
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/', Rule::unique('users', 'phone')],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
        ], ['phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.']);

        $user = User::create($data + [
            'role_id' => Role::key(Role::COMPANY)->id,
            'operating_company_id' => $company->id,
            'active' => true,
        ]);

        $this->log('إنشاء حساب موظف', $company, $user->name);

        return back()->with('status', "تم إنشاء حساب «{$user->name}» — يدخل بوابة الشركة بجواله.");
    }

    private function validated(Request $request, ?OperatingCompany $company = null): array
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'commercial_register' => ['nullable', 'string', 'max:20', Rule::unique('operating_companies', 'commercial_register')->ignore($company)],
            'phone' => ['nullable', 'string', 'regex:/^05\d{8}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(OperatingCompany::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
            'commercial_register.unique' => 'هذا السجل التجاري لشركة أخرى.',
        ]);
    }

    private function log(string $action, OperatingCompany $company, ?string $note = null): void
    {
        AuditLog::create([
            'user_email' => request()->user()->email ?? request()->user()->phone,
            'role' => request()->user()->app_role_key ?? 'admin',
            'action' => $action,
            'entity' => 'OperatingCompany',
            'record_label' => $company->name,
            'details' => trim("{$action} — شركة تشغيل ".($note ? "({$note})" : '')),
            'ip' => request()->ip(),
        ]);
    }
}
