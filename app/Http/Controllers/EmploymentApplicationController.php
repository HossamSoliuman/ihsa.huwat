<?php

namespace App\Http\Controllers;

use App\Actions\CreateEmploymentApplicationAction;
use App\Http\Requests\StoreEmploymentApplicationRequest;
use App\Models\EmploymentJob;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class EmploymentApplicationController extends Controller
{
    public function create(EmploymentJob $job): View
    {
        $this->ensureOpen($job);

        return view('employment.applications.create', [
            'job' => $job->load('port'),
            'ports' => Port::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(
        StoreEmploymentApplicationRequest $request,
        EmploymentJob $job,
        CreateEmploymentApplicationAction $createApplication,
    ): RedirectResponse {
        $attributes = Arr::except($request->validated(), [
            'website', 'cv_file', 'identity_file', 'certificate_files',
        ]);
        $application = $createApplication->handle($job, $attributes, $this->uploads($request));

        $request->session()->put('employment_receipts.'.$application->reference_no, [
            'reference' => $application->reference_no,
            'job_title' => $job->title_ar,
            'job_reference' => $job->reference_no,
            'email' => $application->email,
            'submitted_at' => now()->timestamp,
        ]);

        return redirect()->route('applications.submitted', $application->reference_no);
    }

    public function submitted(Request $request, string $reference): View
    {
        abort_unless(preg_match('/^APP-[A-F0-9]{24}$/', $reference), 404);

        return view('employment.applications.submitted', [
            'reference' => $reference,
            'receipt' => $request->session()->get('employment_receipts.'.$reference),
        ]);
    }

    private function ensureOpen(EmploymentJob $job): void
    {
        abort_unless(EmploymentJob::query()->open()->whereKey($job->getKey())->exists(), 404);
    }

    /**
     * @return array<int, array{file: UploadedFile, type: string}>
     */
    private function uploads(StoreEmploymentApplicationRequest $request): array
    {
        $uploads = [['file' => $request->file('cv_file'), 'type' => 'cv']];

        if ($request->hasFile('identity_file')) {
            $uploads[] = ['file' => $request->file('identity_file'), 'type' => 'identity'];
        }

        foreach ($request->file('certificate_files', []) as $certificate) {
            $uploads[] = ['file' => $certificate, 'type' => 'certificate'];
        }

        return $uploads;
    }
}
