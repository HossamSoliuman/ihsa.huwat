<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Models\InformationSubmission;

final class LicenceExpiryPanel
{
    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $today = today()->toDateString();
        $withinThirty = today()->addDays(30)->toDateString();
        $withinNinety = today()->addDays(90)->toDateString();
        $counts = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->where('status', 'approved')
            ->whereNotNull('license_expiry_date')
            ->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date < ? THEN 1 ELSE 0 END), 0) AS expired', [$today])
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date >= ? AND license_expiry_date <= ? THEN 1 ELSE 0 END), 0) AS within_30', [$today, $withinThirty])
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date > ? AND license_expiry_date <= ? THEN 1 ELSE 0 END), 0) AS within_90', [$withinThirty, $withinNinety])
            ->selectRaw('COALESCE(SUM(CASE WHEN license_expiry_date > ? THEN 1 ELSE 0 END), 0) AS later', [$withinNinety])
            ->first();
        $risk = [
            'expired' => (int) $counts->expired,
            'within_30' => (int) $counts->within_30,
            'within_90' => (int) $counts->within_90,
            'later' => (int) $counts->later,
        ];

        return [
            'expiryRisk' => $risk,
            'licenceExpiry' => [
                'label' => 'رخص المراكب',
                'segments' => [
                    ['label' => 'منتهية', 'value' => $risk['expired'], 'tone' => 'status-rejected'],
                    ['label' => 'خلال 30 يوماً', 'value' => $risk['within_30'], 'tone' => 'status-edit'],
                    ['label' => 'خلال 90 يوماً', 'value' => $risk['within_90'], 'tone' => 'slot-3'],
                    ['label' => 'لاحقاً', 'value' => $risk['later'], 'tone' => 'status-approved'],
                ],
            ],
        ];
    }
}
