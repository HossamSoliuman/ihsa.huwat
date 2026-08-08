<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\InformationSubmission;
use App\Models\InformationSubmissionEvent;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class ReviewHealthPanel
{
    private const OPEN_STATUSES = ['submitted', 'under_review', 'needs_edit'];

    private const REVIEW_SLA_DAYS = 7;

    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $summary = $this->summary($scope, $scope->currentStart, $scope->currentEnd);
        $previousSummary = $scope->previousStart === null
            ? $this->emptySummary()
            : $this->summary($scope, $scope->previousStart, $scope->previousEnd);
        $expiryRisk = $this->expiryRisk($scope);
        $kpis = $this->kpis($summary, $previousSummary, $expiryRisk, $scope);

        return [
            'statusCounts' => $this->globalStatusCounts(),
            'hero' => $this->hero($summary, $previousSummary, $scope),
            'kpis' => $kpis,
            'workflowKpis' => collect($kpis)->reject(fn (array $kpi): bool => in_array($kpi['label'], [
                'تجاوزت '.self::REVIEW_SLA_DAYS.' أيام',
                'أقدم طلب معلّق',
                'متوسط زمن القرار',
                'نسبة إعادة الإرسال',
            ], true))->values()->all(),
            'pipeline' => $this->pipeline($summary['statuses'], $scope),
            'trend' => $this->trend($scope),
            'reviewers' => $this->reviewers($scope),
        ];
    }

    /** @return array<string, mixed> */
    private function summary(DashboardScope $scope, ?Carbon $start, ?Carbon $end): array
    {
        $aggregate = $scope->applySubmissions(InformationSubmission::query(), $start, $end)
            ->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('submitted', 'under_review', 'needs_edit') THEN 1 ELSE 0 END), 0) AS open_count")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END), 0) AS rejected")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END), 0) AS submitted")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END), 0) AS under_review")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'needs_edit' THEN 1 ELSE 0 END), 0) AS needs_edit")
            ->selectRaw("MIN(CASE WHEN status IN ('submitted', 'under_review', 'needs_edit') THEN submitted_at END) AS oldest_open")
            ->selectRaw("COALESCE(SUM(CASE WHEN status IN ('submitted', 'under_review', 'needs_edit') AND submitted_at < ? THEN 1 ELSE 0 END), 0) AS over_sla", [now()->subDays(self::REVIEW_SLA_DAYS)])
            ->first();
        $decisionDays = $this->decisionDays($scope, $start, $end);
        $reworked = $this->reworkedCount($scope, $start, $end);
        $decided = (int) $aggregate->approved + (int) $aggregate->rejected;

        return [
            'total' => (int) $aggregate->total,
            'open' => (int) $aggregate->open_count,
            'approval_rate' => $decided > 0 ? ((int) $aggregate->approved / $decided) * 100 : null,
            'decision_median' => $this->median($decisionDays),
            'rework_rate' => (int) $aggregate->total > 0 ? ($reworked / (int) $aggregate->total) * 100 : null,
            'oldest_backlog' => $aggregate->oldest_open === null
                ? null
                : Carbon::parse($aggregate->oldest_open)->diffInSeconds(now()) / 86400,
            'over_sla' => (int) $aggregate->over_sla,
            'statuses' => [
                'submitted' => (int) $aggregate->submitted,
                'under_review' => (int) $aggregate->under_review,
                'needs_edit' => (int) $aggregate->needs_edit,
                'approved' => (int) $aggregate->approved,
                'rejected' => (int) $aggregate->rejected,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function emptySummary(): array
    {
        return [
            'total' => 0,
            'open' => 0,
            'approval_rate' => null,
            'decision_median' => null,
            'rework_rate' => null,
            'oldest_backlog' => null,
            'over_sla' => 0,
            'statuses' => array_fill_keys(InformationSubmission::STATUSES, 0),
        ];
    }

    /** @return Collection<int, float> */
    private function decisionDays(DashboardScope $scope, ?Carbon $start, ?Carbon $end): Collection
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->whereIn($submissionsTable.'.status', ['approved', 'rejected'])
            ->whereIn($eventsTable.'.to_status', ['approved', 'rejected'])
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->when($start, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($submissionsTable.'.submitted_at', '>=', $date))
            ->when($end, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($submissionsTable.'.submitted_at', '<=', $date))
            ->selectRaw("{$submissionsTable}.submitted_at, MIN({$eventsTable}.created_at) AS decided_at")
            ->groupBy($eventsTable.'.submission_id', $submissionsTable.'.submitted_at')
            ->get()
            ->map(fn (object $row): float => Carbon::parse($row->submitted_at)->diffInSeconds(Carbon::parse($row->decided_at), false) / 86400)
            ->filter(fn (float $days): bool => $days >= 0)
            ->values();
    }

    private function reworkedCount(DashboardScope $scope, ?Carbon $start, ?Carbon $end): int
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->where($eventsTable.'.to_status', 'needs_edit')
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->when($start, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($submissionsTable.'.submitted_at', '>=', $date))
            ->when($end, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($submissionsTable.'.submitted_at', '<=', $date))
            ->distinct()
            ->count($eventsTable.'.submission_id');
    }

    /** @param  Collection<int, float>  $values */
    private function median(Collection $values): ?float
    {
        if ($values->isEmpty()) {
            return null;
        }

        $sorted = $values->sort()->values();
        $middle = intdiv($sorted->count(), 2);

        return $sorted->count() % 2 === 1
            ? (float) $sorted[$middle]
            : ((float) $sorted[$middle - 1] + (float) $sorted[$middle]) / 2;
    }

    /** @param  array<string, mixed>  $summary */
    private function hero(array $summary, array $previousSummary, DashboardScope $scope): array
    {
        return $this->tile(
            'إجمالي الطلبات',
            $summary['total'],
            'inbox',
            $this->queueUrl->submissions($scope),
            $this->relativeDelta($summary['total'], $previousSummary['total']),
        );
    }

    /** @return list<array<string, mixed>> */
    private function kpis(array $summary, array $previousSummary, array $expiryRisk, DashboardScope $scope): array
    {
        $expiringSoon = array_sum([$expiryRisk['expired'], $expiryRisk['within_30'], $expiryRisk['within_90']]);

        return [
            $this->tile('قيد المعالجة', $summary['open'], 'clock', $this->queueUrl->submissions($scope, 'submitted')),
            $this->tile('تجاوزت '.self::REVIEW_SLA_DAYS.' أيام', $summary['over_sla'], 'alert', $this->queueUrl->submissions($scope, 'submitted'), tone: $summary['over_sla'] > 0 ? 'danger' : 'good'),
            $this->tile('أقدم طلب معلّق', $summary['oldest_backlog'] === null ? null : (int) floor($summary['oldest_backlog']), 'hourglass', $this->queueUrl->submissions($scope, 'submitted'), suffix: 'يوم', tone: ($summary['oldest_backlog'] ?? 0) > self::REVIEW_SLA_DAYS ? 'warn' : null),
            $this->tile('متوسط زمن القرار', $summary['decision_median'] === null ? null : round($summary['decision_median'], 1), 'gauge', delta: $this->absoluteDelta($summary['decision_median'], $previousSummary['decision_median']), suffix: 'يوم', deltaGood: false, deltaSuffix: 'يوم'),
            $this->tile('نسبة الاعتماد', $summary['approval_rate'] === null ? null : round($summary['approval_rate'], 1), 'check', $this->queueUrl->submissions($scope, 'approved'), $this->absoluteDelta($summary['approval_rate'], $previousSummary['approval_rate']), '%', deltaSuffix: 'نقطة'),
            $this->tile('نسبة إعادة الإرسال', $summary['rework_rate'] === null ? null : round($summary['rework_rate'], 1), 'repeat', $this->queueUrl->submissions($scope, 'needs_edit'), $this->absoluteDelta($summary['rework_rate'], $previousSummary['rework_rate']), '%', deltaGood: false, deltaSuffix: 'نقطة'),
            $this->tile('رخص تحتاج متابعة', $expiringSoon, 'shield', $this->queueUrl->submissions($scope, 'approved', ['expiry' => 'risk']), tone: $expiryRisk['expired'] > 0 ? 'danger' : ($expiringSoon > 0 ? 'warn' : null)),
        ];
    }

    /** @return array<string, mixed> */
    private function tile(string $label, int|float|null $value, string $icon, ?string $href = null, ?float $delta = null, ?string $suffix = null, ?string $tone = null, bool $deltaGood = true, string $deltaSuffix = '%'): array
    {
        return [
            'label' => $label,
            'value' => $value,
            'suffix' => $suffix,
            'icon' => $icon,
            'tone' => $tone,
            'delta' => $delta,
            'delta_good' => $deltaGood,
            'delta_suffix' => $deltaSuffix,
            'href' => $href,
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

    /** @param  array<string, int>  $statuses */
    private function pipeline(array $statuses, DashboardScope $scope): array
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
                'href' => $this->queueUrl->submissions($scope, $status),
            ])->all(),
        ];
    }

    /** @return array<string, mixed> */
    private function trend(DashboardScope $scope): array
    {
        $chartStart = $scope->currentStart ?? $scope->applySubmissions(InformationSubmission::query(), null, $scope->currentEnd)
            ->min('submitted_at');
        $chartStart = $chartStart ? Carbon::parse($chartStart)->startOfDay() : $scope->currentEnd->copy()->startOfDay();
        $isDaily = $chartStart->diffInDays($scope->currentEnd) <= 30;
        $arrivals = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->toBase()
            ->selectRaw('DATE(submitted_at) AS bucket, COUNT(*) AS aggregate')
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');
        $clearances = $this->decisionEventQuery($scope)
            ->selectRaw('DATE(information_submission_events.created_at) AS bucket, COUNT(*) AS aggregate')
            ->groupBy('bucket')
            ->pluck('aggregate', 'bucket');

        if (! $isDaily) {
            $arrivals = $this->weeklyBuckets($arrivals);
            $clearances = $this->weeklyBuckets($clearances);
        }

        $arrivalValues = [];
        $clearanceValues = [];
        $cursor = $isDaily ? $chartStart->copy() : $chartStart->copy()->startOfWeek();
        $end = $isDaily ? $scope->currentEnd->copy()->startOfDay() : $scope->currentEnd->copy()->startOfWeek();

        while ($cursor->lte($end)) {
            $key = $isDaily ? $cursor->format('Y-m-d') : $cursor->format('o-W');
            $arrivalValues[] = ['label' => $cursor->format('d/m'), 'value' => (int) $arrivals->get($key, 0)];
            $clearanceValues[] = ['label' => $cursor->format('d/m'), 'value' => (int) $clearances->get($key, 0)];
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

    /** @param  Collection<string, int|string>  $daily */
    private function weeklyBuckets(Collection $daily): Collection
    {
        return $daily->reduce(function (Collection $weeks, int|string $count, string $date): Collection {
            $key = Carbon::parse($date)->format('o-W');
            $weeks[$key] = (int) $weeks->get($key, 0) + (int) $count;

            return $weeks;
        }, collect());
    }

    /** @return list<array<string, mixed>> */
    private function reviewers(DashboardScope $scope): array
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $usersTable = (new User)->getTable();
        $rows = $this->decisionEventQuery($scope)
            ->leftJoin($usersTable, $usersTable.'.id', '=', $eventsTable.'.actor_user_id')
            ->whereNotNull($eventsTable.'.actor_user_id')
            ->selectRaw("{$eventsTable}.actor_user_id, {$usersTable}.full_name AS actor_name, COUNT(*) AS decisions")
            ->selectRaw("COALESCE(SUM(CASE WHEN {$eventsTable}.to_status = 'approved' THEN 1 ELSE 0 END), 0) AS approved")
            ->groupBy($eventsTable.'.actor_user_id', $usersTable.'.full_name')
            ->orderByDesc('decisions')
            ->get();
        $firstDecisions = $this->firstDecisionRows($scope)->groupBy('actor_user_id');
        $reworkedIds = $this->reworkedSubmissionIds($scope);
        $actorSubmissions = $this->decisionEventQuery($scope)
            ->select([$eventsTable.'.actor_user_id', $eventsTable.'.submission_id'])
            ->distinct()
            ->get()
            ->groupBy('actor_user_id');

        return $rows->map(function (object $row) use ($firstDecisions, $reworkedIds, $actorSubmissions): array {
            $actorId = (int) $row->actor_user_id;
            $decisionDays = $firstDecisions->get($actorId, collect())->map(
                fn (object $event): float => Carbon::parse($event->submitted_at)->diffInSeconds(Carbon::parse($event->decided_at), false) / 86400,
            )->filter(fn (float $days): bool => $days >= 0)->values();
            $submissionIds = $actorSubmissions->get($actorId, collect())->pluck('submission_id')->unique();

            return [
                'name' => $row->actor_name ?: 'مستخدم محذوف',
                'decisions' => (int) $row->decisions,
                'median_days' => $this->median($decisionDays),
                'approval_share' => ((int) $row->approved / (int) $row->decisions) * 100,
                'rework_share' => $submissionIds->isEmpty() ? 0 : ($submissionIds->intersect($reworkedIds)->count() / $submissionIds->count()) * 100,
            ];
        })->all();
    }

    private function decisionEventQuery(DashboardScope $scope): QueryBuilder
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->whereIn($eventsTable.'.to_status', ['approved', 'rejected'])
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->when($scope->currentStart, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($eventsTable.'.created_at', '>=', $date))
            ->where($eventsTable.'.created_at', '<=', $scope->currentEnd);
    }

    /** @return Collection<int, object> */
    private function firstDecisionRows(DashboardScope $scope): Collection
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->whereIn($eventsTable.'.to_status', ['approved', 'rejected'])
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->selectRaw("{$eventsTable}.submission_id, {$eventsTable}.actor_user_id, {$submissionsTable}.submitted_at, MIN({$eventsTable}.created_at) AS decided_at")
            ->groupBy($eventsTable.'.submission_id', $eventsTable.'.actor_user_id', $submissionsTable.'.submitted_at')
            ->get();
    }

    /** @return Collection<int, int> */
    private function reworkedSubmissionIds(DashboardScope $scope): Collection
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->where($eventsTable.'.to_status', 'needs_edit')
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->distinct()
            ->pluck($eventsTable.'.submission_id');
    }

    /** @return array<string, int> */
    private function expiryRisk(DashboardScope $scope): array
    {
        $today = today()->toDateString();
        $thirty = today()->addDays(30)->toDateString();
        $ninety = today()->addDays(90)->toDateString();
        $counts = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->where('status', 'approved')
            ->whereNotNull('license_expiry_date')
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date < ? THEN 1 ELSE 0 END), 0) AS expired', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date >= ? AND license_expiry_date <= ? THEN 1 ELSE 0 END), 0) AS within_30', [$today, $thirty])
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date > ? AND license_expiry_date <= ? THEN 1 ELSE 0 END), 0) AS within_90', [$thirty, $ninety])
            ->first();

        return ['expired' => (int) $counts->expired, 'within_30' => (int) $counts->within_30, 'within_90' => (int) $counts->within_90];
    }

    /** @return array<string, int> */
    private function globalStatusCounts(): array
    {
        $counts = InformationSubmission::query()->toBase()
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
