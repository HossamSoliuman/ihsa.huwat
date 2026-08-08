<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\InformationSubmission;
use App\Models\InformationSubmissionEvent;
use App\Models\User;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

final class AttentionPanel
{
    private const OPEN_STATUSES = ['submitted', 'under_review', 'needs_edit'];

    private const REVIEW_SLA_DAYS = 7;

    private const LIMIT = 6;

    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        return [
            'decisionQueue' => $this->decisionQueue($scope),
            'expiringLicenses' => $this->expiringLicenses($scope),
            'recentActivity' => $this->recentActivity($scope),
        ];
    }

    /** @return array<string, mixed> */
    private function decisionQueue(DashboardScope $scope): array
    {
        $query = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->whereIn('status', self::OPEN_STATUSES);
        $aggregate = (clone $query)->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN submitted_at < ? THEN 1 ELSE 0 END), 0) AS overdue', [now()->subDays(self::REVIEW_SLA_DAYS)])
            ->first();
        $items = (clone $query)
            ->with('port:id,name')
            ->oldest('submitted_at')
            ->oldest('id')
            ->limit(self::LIMIT)
            ->get(['id', 'reference_no', 'status', 'port_id', 'submitted_at'])
            ->map(function (InformationSubmission $submission): array {
                $ageDays = (int) floor($submission->submitted_at->diffInSeconds(now()) / 86400);

                return [
                    'reference' => $submission->reference_no,
                    'port' => $submission->port?->name ?? 'غير محدد',
                    'status' => $submission->status,
                    'age_days' => $ageDays,
                    'overdue' => $ageDays > self::REVIEW_SLA_DAYS,
                    'href' => route('information.admin.show', $submission),
                ];
            })->all();

        return [
            'total' => (int) $aggregate->total,
            'overdue' => (int) $aggregate->overdue,
            'href' => $this->queueUrl->submissions($scope),
            'items' => $items,
        ];
    }

    /** @return array<string, mixed> */
    private function expiringLicenses(DashboardScope $scope): array
    {
        $query = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->where('status', 'approved')
            ->whereNotNull('license_expiry_date')
            ->whereDate('license_expiry_date', '<=', today()->addDays(90));
        $aggregate = (clone $query)->toBase()
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date < ? THEN 1 ELSE 0 END), 0) AS expired', [today()->toDateString()])
            ->first();
        $items = (clone $query)
            ->with('port:id,name')
            ->oldest('license_expiry_date')
            ->oldest('id')
            ->limit(self::LIMIT)
            ->get(['id', 'reference_no', 'port_id', 'license_expiry_date'])
            ->map(function (InformationSubmission $submission): array {
                $daysLeft = (int) today()->diffInDays($submission->license_expiry_date->copy()->startOfDay(), false);

                return [
                    'reference' => $submission->reference_no,
                    'port' => $submission->port?->name ?? 'غير محدد',
                    'expiry' => $submission->license_expiry_date->format('Y-m-d'),
                    'days_left' => $daysLeft,
                    'expired' => $daysLeft < 0,
                    'href' => route('information.admin.show', $submission),
                ];
            })->all();

        return [
            'total' => (int) $aggregate->total,
            'expired' => (int) $aggregate->expired,
            'href' => $this->queueUrl->submissions($scope, 'approved', ['expiry' => 'risk']),
            'items' => $items,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function recentActivity(DashboardScope $scope): array
    {
        $eventsTable = (new InformationSubmissionEvent)->getTable();
        $submissionsTable = (new InformationSubmission)->getTable();
        $usersTable = (new User)->getTable();

        return InformationSubmissionEvent::query()
            ->toBase()
            ->join($submissionsTable, $submissionsTable.'.id', '=', $eventsTable.'.submission_id')
            ->leftJoin($usersTable, $usersTable.'.id', '=', $eventsTable.'.actor_user_id')
            ->when($scope->hasGeographyFilter(), fn (QueryBuilder $query): QueryBuilder => $query->whereIn($submissionsTable.'.port_id', $scope->portIds()))
            ->when($scope->currentStart, fn (QueryBuilder $query, Carbon $date): QueryBuilder => $query->where($submissionsTable.'.submitted_at', '>=', $date))
            ->where($submissionsTable.'.submitted_at', '<=', $scope->currentEnd)
            ->latest($eventsTable.'.created_at')
            ->orderByDesc($eventsTable.'.id')
            ->limit(8)
            ->get([
                $eventsTable.'.submission_id',
                $eventsTable.'.from_status',
                $eventsTable.'.to_status',
                $eventsTable.'.created_at',
                $submissionsTable.'.reference_no',
                $usersTable.'.full_name AS actor_name',
            ])
            ->map(fn (object $event): array => [
                'reference' => $event->reference_no,
                'actor' => $event->actor_name ?: 'النظام',
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'created_at' => Carbon::parse($event->created_at)->locale('ar'),
                'href' => route('information.admin.show', $event->submission_id),
            ])->all();
    }
}
