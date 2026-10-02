<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\OperatingCompany;
use App\Models\Port;
use App\Models\StatisticsOfficer;
use App\Services\Counters\CounterManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * العدّادون كلهم عند المدير العام: عدّادو الوزارة وعدّادو شركات التشغيل
 * في قائمة واحدة. الوزارة لا تعتمد من توظّفه الشركة، لكنها ترى الجميع
 * وتوقف أيًّا منهم (إيقاف لا ترفعه الشركة) وتنقله.
 */
class CounterController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->query('company');

        $counters = StatisticsOfficer::query()
            ->whereNotNull('user_id')
            ->with(['port', 'company.ports', 'user', 'suspender'])
            ->when($company === 'ministry', fn ($q) => $q->whereNull('operating_company_id'))
            ->when(is_numeric($company), fn ($q) => $q->where('operating_company_id', (int) $company))
            ->when($request->filled('port'), fn ($q) => $q->where('port_id', (int) $request->query('port')))
            ->when($request->query('status') === 'suspended', fn ($q) => $q->whereNotNull('suspended_at'))
            ->when($request->query('status') === 'active', fn ($q) => $q->whereNull('suspended_at'))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('employee_number', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $base = StatisticsOfficer::whereNotNull('user_id');

        return view('panel.counters.index', [
            'counters' => $counters,
            'companies' => OperatingCompany::orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'counts' => [
                'total' => (clone $base)->count(),
                'ministry' => (clone $base)->whereNull('operating_company_id')->count(),
                'companies' => (clone $base)->whereNotNull('operating_company_id')->count(),
                'suspended' => (clone $base)->whereNotNull('suspended_at')->count(),
            ],
        ]);
    }

    public function suspend(Request $request, StatisticsOfficer $counter, CounterManagement $management): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $management->suspend($counter, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "أُوقف «{$counter->name}» — لا يدخل التطبيق ولا البوابة حتى يُرفع إيقافه.");
    }

    public function reactivate(Request $request, StatisticsOfficer $counter, CounterManagement $management): RedirectResponse
    {
        $management->reactivate($counter, $request->user());

        return back()->with('status', "رُفع إيقاف «{$counter->name}».");
    }

    public function transfer(Request $request, StatisticsOfficer $counter, CounterManagement $management): RedirectResponse
    {
        $data = $request->validate([
            'to_port_id' => ['required', 'exists:ports,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $to = Port::findOrFail($data['to_port_id']);

        // عدّاد الشركة لا يُنقل إلا إلى ميناء تشغّله شركته.
        if ($counter->company && ! $counter->company->operatesPort($to->id)) {
            return back()->withErrors(['to_port_id' => "{$to->name} لا تشغّله {$counter->company->name}."]);
        }

        $management->transfer($counter, $to, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "نُقل «{$counter->name}» إلى {$to->name}.");
    }
}
