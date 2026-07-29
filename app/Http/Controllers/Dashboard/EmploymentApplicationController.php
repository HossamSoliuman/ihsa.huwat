<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\ProvisionEmployeeAccountAction;
use App\Actions\ReviewEmploymentApplicationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\FilterEmploymentApplicationsRequest;
use App\Http\Requests\ProvisionEmployeeAccountRequest;
use App\Http\Requests\ReviewEmploymentApplicationRequest;
use App\Models\EmploymentApplication;
use App\Models\EmploymentJob;
use App\Models\Port;
use App\Models\Shift;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class EmploymentApplicationController extends Controller
{
    public function index(FilterEmploymentApplicationsRequest $request): View
    {
        $filters = $request->validated();
        $applications = EmploymentApplication::query()
            ->with(['job:id,reference_no,title_ar', 'preferredPort:id,name'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['job_id'] ?? null, fn ($query, $jobId) => $query->where('job_id', $jobId))
            ->when($filters['q'] ?? null, function ($query, $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%")
                        ->orWhereHas('job', fn ($query) => $query->where('title_ar', 'like', "%{$search}%"));
                });
            })
            ->latest('submitted_at')
            ->paginate(30)
            ->withQueryString();

        $stats = EmploymentApplication::query()->selectRaw(
            "COUNT(*) AS total, SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count, SUM(CASE WHEN status IN ('under_review', 'shortlisted', 'interview') THEN 1 ELSE 0 END) AS active_review_count, SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count, SUM(CASE WHEN status = 'account_created' THEN 1 ELSE 0 END) AS accounts_count"
        )->firstOrFail();
        $jobs = EmploymentJob::query()->latest()->get(['id', 'reference_no', 'title_ar']);

        return view('dashboard.recruitment.applications.index', compact('applications', 'stats', 'jobs', 'filters'));
    }

    public function show(EmploymentApplication $application): View
    {
        Gate::authorize('view', $application);
        $application->load([
            'job.port', 'preferredPort', 'reviewer', 'employeeUser',
            'attachments', 'events' => fn ($query) => $query->with('actor')->latest(),
        ]);

        $emailLocalPart = strtolower(strtok($application->email, '@'));
        $suggestedUsername = trim(preg_replace('/[^a-z0-9._-]+/', '.', $emailLocalPart) ?: '', '.-_');

        return view('dashboard.recruitment.applications.show', [
            'application' => $application,
            'ports' => Port::query()->with('governorate')->where('is_active', true)->orderBy('name')->get(),
            'shifts' => Shift::query()->orderBy('start_time')->get(),
            'suggestedUsername' => strlen($suggestedUsername) >= 4 ? substr($suggestedUsername, 0, 100) : 'employee.'.$application->id,
            'suggestedEmployeeNumber' => 'EMP-'.now()->year.'-'.str_pad((string) $application->id, 6, '0', STR_PAD_LEFT),
        ]);
    }

    public function review(ReviewEmploymentApplicationRequest $request, EmploymentApplication $application, ReviewEmploymentApplicationAction $action): RedirectResponse
    {
        $data = $request->validated();
        $action->execute($application, $data['status'], $data['admin_note'] ?? null, $request->user());

        return to_route('dashboard.applications.show', $application)->with('status', 'تم حفظ مراجعة الطلب.');
    }

    public function provision(ProvisionEmployeeAccountRequest $request, EmploymentApplication $application, ProvisionEmployeeAccountAction $action): RedirectResponse
    {
        $data = $request->validated();
        $employee = $action->execute($application, $data, $request->user());

        return to_route('dashboard.applications.show', $application)
            ->with('status', 'تم إنشاء حساب الموظف وربطه بطلب التوظيف.')
            ->with('employment_credentials_once', [
                'full_name' => $employee->user->full_name,
                'username' => $employee->user->username,
                'password' => $data['password'],
                'employee_number' => $employee->employee_number,
            ]);
    }
}
