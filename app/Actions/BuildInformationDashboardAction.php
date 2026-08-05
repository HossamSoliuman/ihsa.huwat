<?php

namespace App\Actions;

use App\Models\InformationSubmission;
use App\Models\InformationSubmissionEvent;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BuildInformationDashboardAction
{
    /** Statuses that still wait for a reviewer decision. */
    private const OPEN_STATUSES = ['submitted', 'under_review', 'needs_edit'];

    /** Days a submission may wait before it is flagged as overdue. */
    private const REVIEW_SLA_DAYS = 7;

    /** Rows listed inside each follow-up panel before linking to the full queue. */
    private const ATTENTION_LIMIT = 6;

    /**
     * @param  array{range?: string|null, port_id?: int|null, region_id?: int|null}  $filters
     * @return array<string, mixed>
     */
    public function execute(array $filters): array
    {
        $normalizedFilters = [
            'range' => $filters['range'] ?? '30',
            'port_id' => isset($filters['port_id']) ? (int) $filters['port_id'] : null,
            'region_id' => isset($filters['region_id']) ? (int) $filters['region_id'] : null,
        ];

        if (app()->environment('testing')) {
            return $this->build($normalizedFilters);
        }

        $fingerprint = sha1((string) json_encode($normalizedFilters));

        /** @var array<string, mixed> $dashboard */
        $dashboard = Cache::remember(
            "information.dashboard.{$fingerprint}",
            300,
            fn (): array => $this->build($normalizedFilters),
        );

        return $dashboard;
    }

    /**
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return array<string, mixed>
     */
    private function build(array $filters): array
    {
        $ports = Port::query()
            ->select(['id', 'governorate_id', 'name'])
            ->with(['governorate:id,region_id,name', 'governorate.region:id,name'])
            ->orderBy('name')
            ->get();

        $regions = $ports
            ->pluck('governorate.region')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        $allowedPortIds = $this->allowedPortIds($ports, $filters);
        $windows = $this->windows($filters['range']);
        $submissions = $this->submissionRows($allowedPortIds, $windows['current_start'], $windows['current_end']);
        $previousSubmissions = $windows['previous_start'] === null
            ? collect()
            : $this->submissionRows($allowedPortIds, $windows['previous_start'], $windows['previous_end']);

        $decisionEvents = $this->decisionEvents($allowedPortIds, $windows['current_start'], $windows['current_end']);
        $historySubmissionIds = $submissions->pluck('id')
            ->merge($previousSubmissions->pluck('id'))
            ->merge($decisionEvents->pluck('submission_id'))
            ->unique()
            ->values();
        $eventHistory = $this->eventHistory($historySubmissionIds);
        $currentEvents = $eventHistory->whereIn('submission_id', $submissions->pluck('id'))->values();
        $previousEvents = $eventHistory->whereIn('submission_id', $previousSubmissions->pluck('id'))->values();

        $summary = $this->summary($submissions, $currentEvents);
        $previousSummary = $this->summary($previousSubmissions, $previousEvents);
        $expiryRisk = $this->expiryRisk($submissions);

        return [
            'filters' => $filters,
            'rangeOptions' => [
                '7' => 'آخر 7 أيام',
                '30' => '30 يوماً',
                '90' => '90 يوماً',
                'year' => 'هذا العام',
                'all' => 'الكل',
            ],
            'ports' => $ports,
            'regions' => $regions,
            'statusCounts' => $this->globalStatusCounts(),
            'hero' => $this->hero($summary, $previousSummary, $filters),
            'kpis' => $this->kpis($summary, $previousSummary, $expiryRisk, $filters),
            'pipeline' => $this->pipeline($summary['statuses'], $filters),
            'trend' => $this->trend($submissions, $decisionEvents, $windows['current_start'], $windows['current_end']),
            'portCoverage' => $this->portCoverage($submissions, $ports, $filters),
            'reviewers' => $this->reviewers($decisionEvents, $eventHistory),
            'decisionQueue' => $this->decisionQueue($submissions, $ports, $filters),
            'expiringLicenses' => $this->expiringLicenses($submissions, $ports, $filters),
            'expiryRisk' => $expiryRisk,
            'recentActivity' => $this->recentActivity($currentEvents),
            'generatedAt' => now()->locale('ar'),
        ];
    }

    /**
     * @param  Collection<int, Port>  $ports
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return Collection<int, int>
     */
    private function allowedPortIds(Collection $ports, array $filters): Collection
    {
        if ($filters['port_id'] !== null) {
            if ($filters['region_id'] !== null) {
                $port = $ports->firstWhere('id', $filters['port_id']);

                if ($port?->governorate?->region_id !== $filters['region_id']) {
                    return collect([0]);
                }
            }

            return collect([$filters['port_id']]);
        }

        if ($filters['region_id'] !== null) {
            return $ports
                ->filter(fn (Port $port): bool => $port->governorate?->region_id === $filters['region_id'])
                ->pluck('id')
                ->values();
        }

        return collect();
    }

    /**
     * @return array{current_start: Carbon|null, current_end: Carbon, previous_start: Carbon|null, previous_end: Carbon|null}
     */
    private function windows(string $range): array
    {
        $currentEnd = now();

        if ($range === 'all') {
            return [
                'current_start' => null,
                'current_end' => $currentEnd,
                'previous_start' => null,
                'previous_end' => null,
            ];
        }

        $currentStart = $range === 'year'
            ? now()->startOfYear()
            : today()->subDays(((int) $range) - 1);
        $periodSeconds = $currentStart->diffInSeconds($currentEnd) + 1;
        $previousEnd = $currentStart->copy()->subSecond();

        return [
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousEnd->copy()->subSeconds($periodSeconds - 1),
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @param  Collection<int, int>  $allowedPortIds
     * @return Collection<int, InformationSubmission>
     */
    private function submissionRows(Collection $allowedPortIds, ?Carbon $start, ?Carbon $end): Collection
    {
        return InformationSubmission::query()
            ->select([
                'id',
                'reference_no',
                'status',
                'port_id',
                'license_expiry_date',
                'submitted_at',
            ])
            ->when($allowedPortIds->isNotEmpty(), fn (Builder $query): Builder => $query->whereIn('port_id', $allowedPortIds))
            ->when($start, fn (Builder $query, Carbon $date): Builder => $query->where('submitted_at', '>=', $date))
            ->when($end, fn (Builder $query, Carbon $date): Builder => $query->where('submitted_at', '<=', $date))
            ->oldest('submitted_at')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $allowedPortIds
     * @return Collection<int, object>
     */
    private function decisionEvents(Collection $allowedPortIds, ?Carbon $start, Carbon $end): Collection
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();
        $usersTable = (new User)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->leftJoin($usersTable, $usersTable.'.id', '=', $eventsTable.'.actor_user_id')
            ->select([
                $eventsTable.'.id',
                $eventsTable.'.submission_id',
                $eventsTable.'.to_status',
                $eventsTable.'.actor_user_id',
                $eventsTable.'.created_at',
                $submissionsTable.'.submitted_at',
                $submissionsTable.'.reference_no',
                $usersTable.'.full_name as actor_name',
            ])
            ->whereIn($eventsTable.'.to_status', ['approved', 'rejected'])
            ->when($allowedPortIds->isNotEmpty(), fn ($query) => $query->whereIn($submissionsTable.'.port_id', $allowedPortIds))
            ->when($start, fn ($query, Carbon $date) => $query->where($eventsTable.'.created_at', '>=', $date))
            ->where($eventsTable.'.created_at', '<=', $end)
            ->oldest($eventsTable.'.created_at')
            ->get();
    }

    /**
     * @param  Collection<int, int>  $submissionIds
     * @return Collection<int, object>
     */
    private function eventHistory(Collection $submissionIds): Collection
    {
        if ($submissionIds->isEmpty()) {
            return collect();
        }

        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();
        $usersTable = (new User)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->leftJoin($usersTable, $usersTable.'.id', '=', $eventsTable.'.actor_user_id')
            ->select([
                $eventsTable.'.id',
                $eventsTable.'.submission_id',
                $eventsTable.'.event_type',
                $eventsTable.'.from_status',
                $eventsTable.'.to_status',
                $eventsTable.'.actor_user_id',
                $eventsTable.'.created_at',
                $submissionsTable.'.submitted_at',
                $submissionsTable.'.reference_no',
                $usersTable.'.full_name as actor_name',
            ])
            ->whereIn($eventsTable.'.submission_id', $submissionIds)
            ->oldest($eventsTable.'.created_at')
            ->oldest($eventsTable.'.id')
            ->get();
    }

    /**
     * @param  Collection<int, InformationSubmission>  $submissions
     * @param  Collection<int, object>  $events
     * @return array<string, mixed>
     */
    private function summary(Collection $submissions, Collection $events): array
    {
        $statuses = collect(InformationSubmission::STATUSES)
            ->mapWithKeys(fn (string $status): array => [$status => $submissions->where('status', $status)->count()])
            ->all();
        $openCount = collect(self::OPEN_STATUSES)->sum(fn (string $status): int => $statuses[$status]);
        $decidedCount = $statuses['approved'] + $statuses['rejected'];
        $firstDecisions = $this->firstEvents($events, ['approved', 'rejected']);
        $decisionDays = $submissions
            ->mapWithKeys(function (InformationSubmission $submission) use ($firstDecisions): array {
                if (! $submission->isDecided()) {
                    return [];
                }

                $event = $firstDecisions->get($submission->id);

                if ($event === null) {
                    return [];
                }

                $days = Carbon::parse($submission->submitted_at)->diffInSeconds(Carbon::parse($event->created_at), false) / 86400;

                return $days >= 0 ? [$submission->id => $days] : [];
            });
        $reworkedIds = $events->where('to_status', 'needs_edit')->pluck('submission_id')->unique();
        $backlogDays = $submissions
            ->whereIn('status', self::OPEN_STATUSES)
            ->map(fn (InformationSubmission $submission): float => Carbon::parse($submission->submitted_at)->diffInSeconds(now()) / 86400);

        return [
            'total' => $submissions->count(),
            'open' => $openCount,
            'decided' => $decidedCount,
            'approval_rate' => $decidedCount > 0 ? ($statuses['approved'] / $decidedCount) * 100 : null,
            'decision_median' => $this->median($decisionDays->values()),
            'decision_days' => $decisionDays,
            'rework_rate' => $submissions->isNotEmpty() ? ($reworkedIds->count() / $submissions->count()) * 100 : null,
            'oldest_backlog' => $backlogDays->max(),
            'over_sla' => $backlogDays->filter(fn (float $days): bool => $days > self::REVIEW_SLA_DAYS)->count(),
            'statuses' => $statuses,
        ];
    }

    /**
     * @param  Collection<int, object>  $events
     * @param  list<string>  $statuses
     * @return Collection<int, object>
     */
    private function firstEvents(Collection $events, array $statuses): Collection
    {
        return $events
            ->filter(fn (object $event): bool => in_array($event->to_status, $statuses, true))
            ->groupBy('submission_id')
            ->map(fn (Collection $submissionEvents): object => $submissionEvents->sortBy('created_at')->first());
    }

    /**
     * @param  Collection<int, int|float>  $values
     */
    private function median(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $middle = intdiv($sorted->count(), 2);

        if ($sorted->count() % 2 === 1) {
            return (float) $sorted[$middle];
        }

        return ((float) $sorted[$middle - 1] + (float) $sorted[$middle]) / 2;
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $previousSummary
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return array<string, mixed>
     */
    private function hero(array $summary, array $previousSummary, array $filters): array
    {
        return [
            'label' => 'إجمالي الطلبات',
            'value' => $summary['total'],
            'suffix' => null,
            'icon' => 'inbox',
            'tone' => null,
            'delta' => $this->relativeDelta($summary['total'], $previousSummary['total']),
            'delta_good' => true,
            'delta_suffix' => '%',
            'href' => $this->queueUrl($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     * @param  array<string, mixed>  $previousSummary
     * @param  array<string, int>  $expiryRisk
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return list<array<string, mixed>>
     */
    private function kpis(array $summary, array $previousSummary, array $expiryRisk, array $filters): array
    {
        $expiringSoon = $expiryRisk['expired'] + $expiryRisk['within_30'] + $expiryRisk['within_90'];

        return [
            [
                'label' => 'قيد المعالجة',
                'value' => $summary['open'],
                'suffix' => null,
                'icon' => 'clock',
                'tone' => null,
                'delta' => null,
                'delta_good' => true,
                'delta_suffix' => '%',
                'href' => $this->queueUrl($filters, 'submitted'),
            ],
            [
                'label' => 'تجاوزت '.self::REVIEW_SLA_DAYS.' أيام',
                'value' => $summary['over_sla'],
                'suffix' => null,
                'icon' => 'alert',
                'tone' => $summary['over_sla'] > 0 ? 'danger' : 'good',
                'delta' => null,
                'delta_good' => false,
                'delta_suffix' => '%',
                'href' => $this->queueUrl($filters, 'submitted'),
            ],
            [
                'label' => 'أقدم طلب معلّق',
                'value' => $summary['oldest_backlog'] === null ? null : (int) floor($summary['oldest_backlog']),
                'suffix' => 'يوم',
                'icon' => 'hourglass',
                'tone' => ($summary['oldest_backlog'] ?? 0) > self::REVIEW_SLA_DAYS ? 'warn' : null,
                'delta' => null,
                'delta_good' => false,
                'delta_suffix' => 'يوم',
                'href' => $this->queueUrl($filters, 'submitted'),
            ],
            [
                'label' => 'متوسط زمن القرار',
                'value' => $summary['decision_median'] === null ? null : round($summary['decision_median'], 1),
                'suffix' => 'يوم',
                'icon' => 'gauge',
                'tone' => null,
                'delta' => $this->absoluteDelta($summary['decision_median'], $previousSummary['decision_median']),
                'delta_good' => false,
                'delta_suffix' => 'يوم',
                'href' => null,
            ],
            [
                'label' => 'نسبة الاعتماد',
                'value' => $summary['approval_rate'] === null ? null : round($summary['approval_rate'], 1),
                'suffix' => '%',
                'icon' => 'check',
                'tone' => null,
                'delta' => $this->absoluteDelta($summary['approval_rate'], $previousSummary['approval_rate']),
                'delta_good' => true,
                'delta_suffix' => 'نقطة',
                'href' => $this->queueUrl($filters, 'approved'),
            ],
            [
                'label' => 'نسبة إعادة الإرسال',
                'value' => $summary['rework_rate'] === null ? null : round($summary['rework_rate'], 1),
                'suffix' => '%',
                'icon' => 'repeat',
                'tone' => null,
                'delta' => $this->absoluteDelta($summary['rework_rate'], $previousSummary['rework_rate']),
                'delta_good' => false,
                'delta_suffix' => 'نقطة',
                'href' => $this->queueUrl($filters, 'needs_edit'),
            ],
            [
                'label' => 'رخص تحتاج متابعة',
                'value' => $expiringSoon,
                'suffix' => null,
                'icon' => 'shield',
                'tone' => $expiryRisk['expired'] > 0 ? 'danger' : ($expiringSoon > 0 ? 'warn' : null),
                'delta' => null,
                'delta_good' => false,
                'delta_suffix' => '%',
                'href' => $this->queueUrl($filters, 'approved', ['expiry' => 'risk']),
            ],
        ];
    }

    private function relativeDelta(int $current, int $previous): ?float
    {
        return $previous > 0 ? round((($current - $previous) / $previous) * 100, 1) : null;
    }

    private function absoluteDelta(float|int|null $current, float|int|null $previous): ?float
    {
        return $current !== null && $previous !== null ? round($current - $previous, 1) : null;
    }

    /**
     * @param  array<string, int>  $statuses
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return array<string, mixed>
     */
    private function pipeline(array $statuses, array $filters): array
    {
        $tones = [
            'submitted' => 'status-submitted',
            'under_review' => 'status-review',
            'needs_edit' => 'status-edit',
            'approved' => 'status-approved',
            'rejected' => 'status-rejected',
        ];

        return [
            'label' => 'الطلبات',
            'segments' => collect(InformationSubmission::STATUSES)->map(fn (string $status): array => [
                'label' => InformationSubmission::STATUS_LABELS[$status],
                'value' => $statuses[$status],
                'tone' => $tones[$status],
                'href' => $this->queueUrl($filters, $status),
            ])->all(),
        ];
    }

    /**
     * @param  Collection<int, InformationSubmission>  $submissions
     * @param  Collection<int, object>  $decisionEvents
     * @return array<string, mixed>
     */
    private function trend(Collection $submissions, Collection $decisionEvents, ?Carbon $start, Carbon $end): array
    {
        $earliest = collect([
            $submissions->min('submitted_at'),
            $decisionEvents->min('created_at'),
        ])->filter()->min();
        $chartStart = $start ?? ($earliest ? Carbon::parse($earliest)->startOfDay() : $end->copy()->startOfDay());
        $isDaily = $chartStart->diffInDays($end) <= 30;
        $bucketStart = $isDaily ? $chartStart->copy()->startOfDay() : $chartStart->copy()->startOfWeek();
        $bucketEnd = $isDaily ? $end->copy()->startOfDay() : $end->copy()->startOfWeek();
        $arrivals = $submissions->countBy(fn (InformationSubmission $submission): string => $this->bucketKey(Carbon::parse($submission->submitted_at), $isDaily));
        $clearances = $decisionEvents->countBy(fn (object $event): string => $this->bucketKey(Carbon::parse($event->created_at), $isDaily));
        $arrivalValues = [];
        $clearanceValues = [];
        $cursor = $bucketStart->copy();

        while ($cursor->lte($bucketEnd)) {
            $key = $this->bucketKey($cursor, $isDaily);
            $label = $cursor->format('d/m');
            $arrivalValues[] = ['label' => $label, 'value' => (int) $arrivals->get($key, 0)];
            $clearanceValues[] = ['label' => $label, 'value' => (int) $clearances->get($key, 0)];
            $isDaily ? $cursor->addDay() : $cursor->addWeek();
        }

        return [
            'series' => [
                ['label' => 'الطلبات الواردة', 'tone' => 'slot-1', 'values' => $arrivalValues],
                ['label' => 'القرارات المنجزة', 'tone' => 'slot-3', 'values' => $clearanceValues],
            ],
            'granularity' => $isDaily ? 'يومي' : 'أسبوعي',
        ];
    }

    private function bucketKey(Carbon $date, bool $isDaily): string
    {
        return $isDaily ? $date->format('Y-m-d') : $date->format('o-W');
    }

    /**
     * @param  Collection<int, object>  $decisionEvents
     * @param  Collection<int, object>  $eventHistory
     * @return list<array<string, mixed>>
     */
    private function reviewers(Collection $decisionEvents, Collection $eventHistory): array
    {
        $firstDecisions = $this->firstEvents($eventHistory, ['approved', 'rejected']);
        $reworkedIds = $eventHistory->where('to_status', 'needs_edit')->pluck('submission_id')->unique();

        return $decisionEvents
            ->whereNotNull('actor_user_id')
            ->groupBy('actor_user_id')
            ->map(function (Collection $events) use ($firstDecisions, $reworkedIds): array {
                $actorId = (int) $events->first()->actor_user_id;
                $firstDecisionDays = $firstDecisions
                    ->filter(fn (object $event): bool => (int) $event->actor_user_id === $actorId)
                    ->map(fn (object $event): float => Carbon::parse($event->submitted_at)->diffInSeconds(Carbon::parse($event->created_at), false) / 86400)
                    ->filter(fn (float $days): bool => $days >= 0)
                    ->values();
                $submissionIds = $events->pluck('submission_id')->unique();

                return [
                    'name' => $events->first()->actor_name ?: 'مستخدم محذوف',
                    'decisions' => $events->count(),
                    'median_days' => $this->median($firstDecisionDays),
                    'approval_share' => ($events->where('to_status', 'approved')->count() / $events->count()) * 100,
                    'rework_share' => $submissionIds->isNotEmpty()
                        ? ($submissionIds->intersect($reworkedIds)->count() / $submissionIds->count()) * 100
                        : 0,
                ];
            })
            ->sortByDesc('decisions')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, InformationSubmission>  $submissions
     * @param  Collection<int, Port>  $ports
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return list<array<string, mixed>>
     */
    private function portCoverage(Collection $submissions, Collection $ports, array $filters): array
    {
        $counts = $submissions->whereNotNull('port_id')->countBy('port_id')->sortDesc();
        $top = $counts->take(8);
        $items = $top->map(fn (int $value, int|string $portId): array => [
            'label' => $ports->firstWhere('id', (int) $portId)?->name ?? 'ميناء غير محدد',
            'value' => $value,
            'tone' => 'slot-1',
            'href' => $this->queueUrl([...$filters, 'port_id' => (int) $portId]),
        ])->values();
        $other = $counts->slice(8)->sum();

        if ($other > 0) {
            $items->push(['label' => 'موانئ أخرى', 'value' => $other, 'tone' => 'slot-3', 'href' => null]);
        }

        return $items->all();
    }

    /**
     * Oldest submissions still waiting for a decision — the queue a supervisor should work top-down.
     *
     * @param  Collection<int, InformationSubmission>  $submissions
     * @param  Collection<int, Port>  $ports
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return array{total: int, overdue: int, href: string, items: list<array<string, mixed>>}
     */
    private function decisionQueue(Collection $submissions, Collection $ports, array $filters): array
    {
        $open = $submissions->whereIn('status', self::OPEN_STATUSES)->sortBy('submitted_at');
        $items = $open->take(self::ATTENTION_LIMIT)->map(function (InformationSubmission $submission) use ($ports): array {
            $ageDays = (int) floor(Carbon::parse($submission->submitted_at)->diffInSeconds(now()) / 86400);

            return [
                'reference' => $submission->reference_no,
                'port' => $ports->firstWhere('id', $submission->port_id)?->name ?? 'غير محدد',
                'status' => $submission->status,
                'age_days' => $ageDays,
                'overdue' => $ageDays > self::REVIEW_SLA_DAYS,
                'href' => route('information.admin.show', $submission->id),
            ];
        })->values()->all();

        return [
            'total' => $open->count(),
            'overdue' => $open->filter(fn (InformationSubmission $submission): bool => Carbon::parse($submission->submitted_at)->diffInSeconds(now()) / 86400 > self::REVIEW_SLA_DAYS)->count(),
            'href' => $this->queueUrl($filters),
            'items' => $items,
        ];
    }

    /**
     * Approved licences that already expired or expire within the 90-day risk window.
     *
     * @param  Collection<int, InformationSubmission>  $submissions
     * @param  Collection<int, Port>  $ports
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @return array{total: int, expired: int, href: string, items: list<array<string, mixed>>}
     */
    private function expiringLicenses(Collection $submissions, Collection $ports, array $filters): array
    {
        $today = today();
        $threshold = $today->copy()->addDays(90);
        $atRisk = $submissions
            ->where('status', 'approved')
            ->filter(fn (InformationSubmission $submission): bool => $submission->license_expiry_date !== null
                && Carbon::parse($submission->license_expiry_date)->startOfDay()->lte($threshold))
            ->sortBy(fn (InformationSubmission $submission): string => Carbon::parse($submission->license_expiry_date)->format('Y-m-d'));

        $items = $atRisk->take(self::ATTENTION_LIMIT)->map(function (InformationSubmission $submission) use ($ports, $today): array {
            $expiry = Carbon::parse($submission->license_expiry_date)->startOfDay();
            $daysLeft = (int) $today->diffInDays($expiry, false);

            return [
                'reference' => $submission->reference_no,
                'port' => $ports->firstWhere('id', $submission->port_id)?->name ?? 'غير محدد',
                'expiry' => $expiry->format('Y-m-d'),
                'days_left' => $daysLeft,
                'expired' => $daysLeft < 0,
                'href' => route('information.admin.show', $submission->id),
            ];
        })->values()->all();

        return [
            'total' => $atRisk->count(),
            'expired' => $atRisk->filter(fn (InformationSubmission $submission): bool => Carbon::parse($submission->license_expiry_date)->startOfDay()->lt($today))->count(),
            'href' => $this->queueUrl($filters, 'approved', ['expiry' => 'risk']),
            'items' => $items,
        ];
    }

    /**
     * @param  Collection<int, InformationSubmission>  $submissions
     * @return array<string, int>
     */
    private function expiryRisk(Collection $submissions): array
    {
        $today = today();
        $counts = ['expired' => 0, 'within_30' => 0, 'within_90' => 0, 'later' => 0];

        foreach ($submissions->where('status', 'approved')->whereNotNull('license_expiry_date') as $submission) {
            $expiryDate = Carbon::parse($submission->license_expiry_date)->startOfDay();

            if ($expiryDate->lt($today)) {
                $counts['expired']++;
            } elseif ($expiryDate->lte($today->copy()->addDays(30))) {
                $counts['within_30']++;
            } elseif ($expiryDate->lte($today->copy()->addDays(90))) {
                $counts['within_90']++;
            } else {
                $counts['later']++;
            }
        }

        return $counts;
    }

    /**
     * @param  Collection<int, object>  $events
     * @return list<array<string, mixed>>
     */
    private function recentActivity(Collection $events): array
    {
        return $events
            ->sortByDesc(fn (object $event): string => $event->created_at.'-'.$event->id)
            ->take(8)
            ->map(fn (object $event): array => [
                'reference' => $event->reference_no,
                'actor' => $event->actor_name ?: 'النظام',
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'created_at' => Carbon::parse($event->created_at)->locale('ar'),
                'href' => route('information.admin.show', $event->submission_id),
            ])
            ->values()
            ->all();
    }

    /** @return array<string, int> */
    private function globalStatusCounts(): array
    {
        $counts = InformationSubmission::query()
            ->toBase()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $statusCounts = ['all' => (int) $counts->sum()];

        foreach (InformationSubmission::STATUSES as $status) {
            $statusCounts[$status] = (int) $counts->get($status, 0);
        }

        return $statusCounts;
    }

    /**
     * @param  array{range: string, port_id: int|null, region_id: int|null}  $filters
     * @param  array<string, string>  $extra
     */
    private function queueUrl(array $filters, ?string $status = null, array $extra = []): string
    {
        $parameters = [
            'status' => $status,
            'range' => $filters['range'],
            'port_id' => $filters['port_id'],
            'region_id' => $filters['region_id'],
            ...$extra,
        ];

        return route('information.admin.index', array_filter($parameters, fn (mixed $value): bool => $value !== null && $value !== ''));
    }
}
