<?php

namespace App\Http\Controllers\Panel\Company;

use App\Http\Controllers\Concerns\ResolvesCompanyRecords;
use App\Http\Controllers\Controller;
use App\Models\CounterApplication;
use App\Services\Counters\CounterApplicationReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * طلبات التوظيف الواردة إلى الشركة (الموثّق جوالها وحدها): الاعتماد ينشئ
 * حساب العدّاد وسجلّه في ميناء الجولة، والرفض يحفظ سببه. الأقدم أولًا.
 */
class ApplicationController extends Controller
{
    use ResolvesCompanyRecords;

    public function index(Request $request): View
    {
        $company = $this->company($request->user());

        $tabs = array_keys(CounterApplication::STATUS_LABELS);
        $status = $request->query('status', CounterApplication::PENDING);
        if (! in_array($status, $tabs, true) && $status !== 'all') {
            $status = CounterApplication::PENDING;
        }

        $base = $company->applications()->verified();

        $applications = (clone $base)
            ->with(['port', 'round', 'reviewer', 'user'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($request->filled('round'), fn ($q) => $q->where('hiring_round_id', (int) $request->query('round')))
            ->when($request->filled('port'), fn ($q) => $q->where('port_id', (int) $request->query('port')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.trim((string) $request->query('search')).'%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('phone', 'like', $term)->orWhere('national_id', 'like', $term));
            })
            ->when($status === CounterApplication::PENDING, fn ($q) => $q->oldest(), fn ($q) => $q->latest())
            ->paginate(20)
            ->withQueryString();

        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('panel.company.applications.index', [
            'applications' => $applications,
            'status' => $status,
            'rounds' => $company->hiringRounds()->latest()->get(['id', 'title']),
            'ports' => $company->ports()->orderBy('name')->get(['ports.id', 'ports.name']),
            'counts' => collect($tabs)->mapWithKeys(fn ($s) => [$s => (int) ($counts[$s] ?? 0)])->put('all', (int) $counts->sum())->all(),
        ]);
    }

    public function approve(Request $request, int $application, CounterApplicationReview $review): RedirectResponse
    {
        $item = $this->companyApplication($this->company($request->user()), $application);
        $user = $review->approve($item, $request->user());

        return back()->with('status', "اعتُمد «{$user->name}» عدّادًا في {$item->port?->name} — يدخل الآن بجواله وكلمة المرور التي اختارها.");
    }

    public function reject(Request $request, int $application, CounterApplicationReview $review): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['nullable', 'string', 'max:1000']]);
        $item = $this->companyApplication($this->company($request->user()), $application);

        $review->reject($item, $request->user(), $data['rejection_reason'] ?? null);

        return back()->with('status', 'رُفض الطلب وأُبلغ صاحبه برسالة.');
    }
}
