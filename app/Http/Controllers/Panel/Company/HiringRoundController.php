<?php

namespace App\Http\Controllers\Panel\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Http\Controllers\Controller;
use App\Models\CounterApplication;
use App\Models\HiringRound;
use App\Models\OperatingCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * جولات التوظيف: الشركة تفتح في أحد موانئها جولة بمقاعد ومدة، فتظهر في
 * صفحة التقديم وفي التطبيق ما دامت مفتوحة وداخل مدتها وفيها مقعد.
 */
class HiringRoundController extends Controller
{
    use ResolvesCompanyRecords;

    public function index(Request $request): View
    {
        $company = $this->company($request->user());

        $rounds = $company->hiringRounds()
            ->with('port')
            ->withCount([
                'applications as approved_count' => fn ($q) => $q->where('status', CounterApplication::APPROVED),
                'applications as pending_count' => fn ($q) => $q->verified()->where('status', CounterApplication::PENDING),
            ])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('panel.company.hiring.index', [
            'company' => $company,
            'rounds' => $rounds,
            'ports' => $company->ports()->orderBy('name')->get(['ports.id', 'ports.name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $this->company($request->user());
        $data = $this->validated($request, $company);

        $company->hiringRounds()->create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('panel.company.hiring')->with('status', $data['status'] === HiringRound::OPEN
            ? 'فُتحت الجولة — تظهر الآن في صفحة التقديم وفي التطبيق.'
            : 'حُفظت الجولة مسودة — افتحها حين تريد استقبال الطلبات.');
    }

    public function update(Request $request, int $round): RedirectResponse
    {
        $company = $this->company($request->user());
        $hiringRound = $this->companyRound($company, $round);
        $data = $this->validated($request, $company, $hiringRound);

        $hiringRound->update($data);

        return back()->with('status', 'تم تحديث الجولة.');
    }

    public function open(Request $request, int $round): RedirectResponse
    {
        $hiringRound = $this->companyRound($this->company($request->user()), $round);
        $hiringRound->update(['status' => HiringRound::OPEN]);

        return back()->with('status', 'فُتحت الجولة.');
    }

    /**
     * إغلاق الجولة يوقف التقديم فيها؛ ما وصل من طلبات يبقى قابلًا للمراجعة.
     */
    public function close(Request $request, int $round): RedirectResponse
    {
        $hiringRound = $this->companyRound($this->company($request->user()), $round);
        $hiringRound->update(['status' => HiringRound::CLOSED]);

        return back()->with('status', 'أُغلقت الجولة — الطلبات الواردة فيها تبقى بانتظار مراجعتك.');
    }

    private function validated(Request $request, OperatingCompany $company, ?HiringRound $round = null): array
    {
        $data = $request->validate([
            'port_id' => ['required', 'integer', Rule::exists('operating_company_ports', 'port_id')->where('operating_company_id', $company->id)],
            'title' => ['required', 'string', 'max:255'],
            'seats' => ['required', 'integer', 'min:1', 'max:500'],
            'opens_at' => ['required', 'date'],
            'closes_at' => ['required', 'date', 'after_or_equal:opens_at'],
            'status' => ['required', Rule::in(array_keys(HiringRound::STATUS_LABELS))],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'port_id.exists' => 'اختر ميناءً من موانئ شركتك.',
            'closes_at.after_or_equal' => 'تاريخ الإغلاق بعد تاريخ الفتح.',
        ]);

        if ($round !== null) {
            $approved = $round->approvedCount();
            if ($data['seats'] < $approved) {
                $request->validate(['seats' => ['min:'.$approved]], ['seats.min' => "اعتُمد في الجولة {$approved} — لا تقلّ المقاعد عنهم."]);
            }

            // ميناء جولة فيها طلبات لا يتغيّر: الطلبات قُدّمت لذلك الميناء.
            if ((int) $data['port_id'] !== $round->port_id && $round->applications()->exists()) {
                $request->validate(['port_id' => ['in:'.$round->port_id]], ['port_id.in' => 'في الجولة طلبات — لا يتغيّر ميناؤها.']);
            }
        }

        return $data;
    }
}
