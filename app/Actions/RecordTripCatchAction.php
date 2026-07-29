<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordTripCatchAction
{
    public function execute(Trip $trip, User $user, array $catches): Trip
    {
        return DB::transaction(function () use ($trip, $user, $catches): Trip {
            $trip = Trip::query()->lockForUpdate()->findOrFail($trip->id);
            $employee = Employee::query()->where('user_id', $user->id)->firstOrFail();

            if ($trip->status !== 'counting' || $trip->assigned_employee_id !== $employee->id) {
                throw ValidationException::withMessages(['trip' => 'الرحلة ليست متاحة لتسجيل المصيد.']);
            }

            $rows = collect($catches)->filter(fn (array $catch): bool => (float) ($catch['reported_kg'] ?? 0) > 0 || (float) ($catch['verified_kg'] ?? 0) > 0)
                ->map(fn (array $catch): array => [
                    'species_id' => $catch['species_id'],
                    'captain_reported_kg' => (float) ($catch['reported_kg'] ?? 0),
                    'verified_kg' => (float) ($catch['verified_kg'] ?? 0),
                    'boxes_count' => (int) ($catch['boxes_count'] ?? 0),
                    'is_unreported_by_captain' => (float) ($catch['reported_kg'] ?? 0) <= 0 && (float) ($catch['verified_kg'] ?? 0) > 0,
                ])->values();

            $totalReported = (float) $rows->sum('captain_reported_kg');
            $totalVerified = (float) $rows->sum('verified_kg');
            $differenceKg = round($totalVerified - $totalReported, 2);
            $differencePercent = $totalReported > 0 ? round($differenceKg / $totalReported * 100, 2) : ($totalVerified > 0 ? 100.0 : 0.0);
            $severity = $this->severity($differencePercent);
            $requiresReview = in_array($severity, ['medium', 'major'], true);

            $trip->catchDetails()->delete();
            $trip->discrepancies()->delete();
            $trip->catchDetails()->createMany($rows->all());
            $trip->update([
                'captain_reported_weight' => $totalReported, 'verified_weight' => $totalVerified,
                'counting_ended_at' => now(), 'status' => $requiresReview ? 'pending_review' : 'approved',
                'approved_by' => $requiresReview ? null : $user->id, 'approved_at' => $requiresReview ? null : now(),
            ]);

            if ($severity !== null) {
                $trip->discrepancies()->create([
                    'diff_kg' => $differenceKg, 'diff_percent' => $differencePercent, 'severity' => $severity,
                    'review_status' => $requiresReview ? 'pending' : 'approved',
                    'reviewed_by' => $requiresReview ? null : $user->id, 'reviewed_at' => $requiresReview ? null : now(),
                ]);
            }

            return $trip;
        });
    }

    private function severity(float $differencePercent): ?string
    {
        $absoluteDifference = abs($differencePercent);

        return match (true) {
            $absoluteDifference > config('discrepancies.thresholds.major') => 'major',
            $absoluteDifference >= config('discrepancies.thresholds.medium') => 'medium',
            $absoluteDifference >= config('discrepancies.thresholds.minor') => 'minor',
            default => null,
        };
    }
}
