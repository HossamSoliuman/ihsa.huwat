<?php

namespace App\Http\Controllers;

use App\Http\Middleware\EnsureInformationIdentity;
use App\Models\InformationSubmission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InformationStatusController extends Controller
{
    /**
     * Tracker for the identity confirmed on the landing page. The identity itself is
     * guaranteed by the "information.identity" middleware.
     */
    public function index(Request $request): View|RedirectResponse
    {
        /** @var array{national_id: string, phone: string} $identity */
        $identity = EnsureInformationIdentity::verified($request);

        $submissions = InformationSubmission::query()
            ->forIdentity($identity)
            ->with(['port:id,name', 'boat:id,name,registration_no'])
            ->latest('submitted_at')
            ->get();

        /** Nothing filed yet under this identity, so there is only one place to go. */
        if ($submissions->isEmpty()) {
            return to_route('information.create');
        }

        $reference = $request->string('reference')->toString();
        $submission = $reference !== ''
            ? $submissions->firstWhere('reference_no', $reference)
            : $submissions->first();

        abort_unless($submission !== null, 404);

        $submission->load(['events' => fn ($query) => $query->oldest('created_at')->oldest('id')]);

        return view('information.status.index', [
            'submissions' => $submissions,
            'submission' => $submission,
            'timeline' => $this->timeline($submission),
        ]);
    }

    /**
     * Build the milestone track shown to the applicant, marking each step against
     * the submission's recorded events.
     *
     * @return list<array{key: string, label: string, state: string, at: ?Carbon, note: ?string}>
     */
    private function timeline(InformationSubmission $submission): array
    {
        $events = $submission->events;
        $reviewStartedAt = $events->firstWhere('to_status', 'under_review')?->created_at;
        $editEvent = $events->firstWhere('to_status', 'needs_edit');
        $decisionEvent = $events->first(
            fn (object $event): bool => in_array($event->to_status, ['approved', 'rejected'], true),
        );

        $milestones = [
            ['key' => 'submitted', 'label' => 'تم إرسال الطلب', 'at' => $submission->submitted_at, 'note' => null],
            ['key' => 'received', 'label' => 'تم استلام الطلب', 'at' => $submission->submitted_at, 'note' => null],
            ['key' => 'under_review', 'label' => 'تحت المراجعة', 'at' => $reviewStartedAt, 'note' => null],
        ];

        if ($editEvent !== null) {
            $milestones[] = [
                'key' => 'needs_edit',
                'label' => 'بانتظار التعديل',
                'at' => $editEvent->created_at,
                'note' => $editEvent->note,
            ];
        }

        $milestones[] = [
            'key' => 'decision',
            'label' => match ($submission->status) {
                'approved' => 'تم اعتماد الطلب',
                'rejected' => 'تم رفض الطلب',
                default => 'انتظار القرار',
            },
            'at' => $decisionEvent?->created_at,
            'note' => $decisionEvent?->note,
        ];

        $isDecided = $submission->isDecided();
        $currentMarked = false;

        return array_map(function (array $milestone) use (&$currentMarked, $isDecided, $submission): array {
            if ($milestone['at'] !== null) {
                $state = $milestone['key'] === 'decision' && $submission->status === 'rejected' ? 'rejected' : 'done';

                return [...$milestone, 'state' => $state];
            }

            /** A decided submission has cleared every earlier milestone, even unrecorded ones. */
            if ($isDecided) {
                return [...$milestone, 'state' => 'done'];
            }

            $state = $currentMarked ? 'upcoming' : 'current';
            $currentMarked = true;

            return [...$milestone, 'state' => $state];
        }, $milestones);
    }
}
