<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveEmploymentJobAction;
use App\Actions\TransitionEmploymentJobAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterEmploymentJobsRequest;
use App\Http\Requests\StoreEmploymentJobRequest;
use App\Http\Requests\TransitionEmploymentJobRequest;
use App\Http\Requests\UpdateEmploymentJobRequest;
use App\Models\EmploymentJob;
use App\Models\Port;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class EmploymentJobController extends Controller
{
    public function index(FilterEmploymentJobsRequest $request): View
    {
        $filters = $request->validated();
        $jobs = EmploymentJob::query()
            ->with('port')
            ->withCount([
                'applications',
                'applications as accepted_applications_count' => fn ($query) => $query->whereIn('status', ['accepted', 'account_created']),
            ])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('title_ar', 'like', "%{$search}%")
                        ->orWhere('department', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'draft' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END")
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = EmploymentJob::query()->selectRaw(
            "COUNT(*) AS total, SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count, SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count, SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) AS closed_count, SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived_count"
        )->firstOrFail();

        return view('dashboard.recruitment.jobs.index', compact('jobs', 'stats', 'filters'));
    }

    public function create(): View
    {
        abort_unless(auth()->user()->can('create', EmploymentJob::class), 403);

        return view('dashboard.recruitment.jobs.form', [
            'job' => new EmploymentJob,
            'ports' => $this->ports(),
        ]);
    }

    public function store(StoreEmploymentJobRequest $request, SaveEmploymentJobAction $action): RedirectResponse
    {
        $job = $action->execute(null, $request->validated(), $request->user());

        return to_route('dashboard.jobs.edit', $job)->with('status', 'تم إنشاء مسودة الفرصة الوظيفية.');
    }

    public function edit(EmploymentJob $job): View
    {
        abort_unless(auth()->user()->can('update', $job), 403);

        return view('dashboard.recruitment.jobs.form', [
            'job' => $job,
            'ports' => $this->ports(),
        ]);
    }

    public function update(UpdateEmploymentJobRequest $request, EmploymentJob $job, SaveEmploymentJobAction $action): RedirectResponse
    {
        $action->execute($job, $request->validated(), $request->user());

        return to_route('dashboard.jobs.edit', $job)->with('status', 'تم حفظ تعديلات الفرصة الوظيفية.');
    }

    public function transition(TransitionEmploymentJobRequest $request, EmploymentJob $job, TransitionEmploymentJobAction $action): RedirectResponse
    {
        $action->execute($job, $request->validated('transition'), $request->user());

        return to_route('dashboard.jobs.index')->with('status', 'تم تحديث حالة الفرصة الوظيفية.');
    }

    private function ports()
    {
        return Port::query()
            ->with('governorate')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
