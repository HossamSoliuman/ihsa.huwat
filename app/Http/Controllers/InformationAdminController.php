<?php

namespace App\Http\Controllers;

use App\Actions\ReviewInformationSubmissionAction;
use App\Http\Requests\FilterInformationSubmissionsRequest;
use App\Http\Requests\ReviewInformationSubmissionRequest;
use App\Models\Governorate;
use App\Models\InformationSubmission;
use App\Models\Port;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InformationAdminController extends Controller
{
    public function index(FilterInformationSubmissionsRequest $request): View
    {
        $filters = $request->validated();

        $submissions = InformationSubmission::query()
            ->with(['port:id,name', 'boat:id,name,registration_no', 'reviewer:id,full_name'])
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['port_id'] ?? null, fn (Builder $query, int $portId): Builder => $query->where('port_id', $portId))
            ->when($filters['region_id'] ?? null, fn (Builder $query, int $regionId): Builder => $query->whereIn(
                'port_id',
                Port::query()->whereIn(
                    'governorate_id',
                    Governorate::query()->where('region_id', $regionId)->select('id'),
                )->select('id'),
            ))
            ->when(($filters['range'] ?? 'all') !== 'all', function (Builder $query) use ($filters): void {
                $start = ($filters['range'] ?? null) === 'year'
                    ? today()->startOfYear()
                    : today()->subDays(((int) ($filters['range'] ?? 30)) - 1);

                $query->where('submitted_at', '>=', $start);
            })
            ->when(($filters['expiry'] ?? null) === 'risk', fn (Builder $query): Builder => $query
                ->where('status', 'approved')
                ->whereNotNull('license_expiry_date')
                ->where('license_expiry_date', '<=', today()->addDays(90)))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('owner_full_name', 'like', "%{$search}%")
                        ->orWhere('owner_national_id', 'like', "%{$search}%")
                        ->orWhere('owner_phone', 'like', "%{$search}%")
                        ->orWhere('license_number', 'like', "%{$search}%");
                });
            })
            ->latest('submitted_at')
            ->paginate(20)
            ->withQueryString();

        return view('information.admin.index', [
            'submissions' => $submissions,
            'filters' => $filters,
            'statusCounts' => $this->statusCounts(),
            'ports' => Port::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(InformationSubmission $submission): View
    {
        $submission->load([
            'port.governorate.region',
            'boat',
            'captain',
            'submitter:id,full_name',
            'reviewer:id,full_name',
            'events' => fn ($query) => $query->with('actor:id,full_name')->latest('created_at')->latest('id'),
        ]);

        return view('information.admin.show', [
            'submission' => $submission,
            'allowedStatuses' => InformationSubmission::TRANSITIONS[$submission->status] ?? [],
        ]);
    }

    public function review(
        ReviewInformationSubmissionRequest $request,
        InformationSubmission $submission,
        ReviewInformationSubmissionAction $reviewInformationSubmission,
    ): RedirectResponse {
        $data = $request->validated();

        $reviewInformationSubmission->execute(
            $submission,
            $data['status'],
            $data['review_notes'] ?? null,
            $request->user(),
        );

        return to_route('information.admin.show', $submission)->with('status', 'تم حفظ مراجعة الطلب.');
    }

    public function document(InformationSubmission $submission, string $category): StreamedResponse
    {
        $path = $category === 'captain_photo'
            ? $submission->captain_photo_path
            : data_get($submission->document_paths, $category);

        abort_unless(is_string($path) && $path !== '', 404);
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, basename($path), [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
            'X-Download-Options' => 'noopen',
        ]);
    }

    /**
     * Submission totals per status, plus an `all` bucket, for the dashboard tiles.
     *
     * @return array<string, int>
     */
    private function statusCounts(): array
    {
        $counts = InformationSubmission::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $statusCounts = ['all' => (int) $counts->sum()];

        foreach (InformationSubmission::STATUSES as $status) {
            $statusCounts[$status] = (int) $counts->get($status, 0);
        }

        return $statusCounts;
    }
}
