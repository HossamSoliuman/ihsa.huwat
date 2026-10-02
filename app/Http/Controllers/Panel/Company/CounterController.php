<?php

namespace App\Http\Controllers\Panel\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Http\Controllers\Controller;
use App\Services\Counters\CompanyDashboard;
use App\Services\Counters\CounterManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * عدّادو الشركة ونشاطهم، وإيقافهم ورفعه، ونقلهم بين موانئ الشركة. إيقاف
 * الوزارة لا يُرفع من هنا (CounterManagement::reactivate).
 */
class CounterController extends Controller
{
    use ResolvesCompanyRecords;

    public function index(Request $request, CompanyDashboard $dashboard): View
    {
        $company = $this->company($request->user());

        $counters = $dashboard->activity($company, null)
            ->when($request->filled('port'), fn ($c) => $c->where('port_id', (int) $request->query('port')))
            ->when($request->query('status') === 'suspended', fn ($c) => $c->filter->isSuspended())
            ->when($request->query('status') === 'active', fn ($c) => $c->reject->isSuspended())
            ->values();

        $counters->load('suspender.appRole');

        return view('panel.company.counters.index', [
            'company' => $company,
            'counters' => $counters,
            'ports' => $company->ports()->orderBy('name')->get(['ports.id', 'ports.name']),
        ]);
    }

    public function suspend(Request $request, int $counter, CounterManagement $management): RedirectResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:1000']]);
        $officer = $this->companyCounter($this->company($request->user()), $counter);

        $management->suspend($officer, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "أُوقف «{$officer->name}» — لا يدخل التطبيق ولا البوابة حتى يُرفع إيقافه.");
    }

    public function reactivate(Request $request, int $counter, CounterManagement $management): RedirectResponse
    {
        $officer = $this->companyCounter($this->company($request->user()), $counter);
        $management->reactivate($officer, $request->user());

        return back()->with('status', "رُفع إيقاف «{$officer->name}».");
    }

    public function transfer(Request $request, int $counter, CounterManagement $management): RedirectResponse
    {
        $data = $request->validate([
            'to_port_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $company = $this->company($request->user());
        $officer = $this->companyCounter($company, $counter);
        $to = $this->companyPort($company, $data['to_port_id']);

        $management->transfer($officer, $to, $request->user(), $data['reason'] ?? null);

        return back()->with('status', "نُقل «{$officer->name}» إلى {$to->name}.");
    }
}
