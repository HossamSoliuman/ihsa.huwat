<?php

namespace App\Actions;

use App\Models\Trip;
use App\Models\TripDiscrepancy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveTripDiscrepancyAction
{
    public function execute(TripDiscrepancy $discrepancy, User $reviewer): void
    {
        DB::transaction(function () use ($discrepancy, $reviewer): void {
            $lockedDiscrepancy = TripDiscrepancy::query()->lockForUpdate()->findOrFail($discrepancy->id);
            $trip = Trip::query()->lockForUpdate()->findOrFail($lockedDiscrepancy->trip_id);

            if ($lockedDiscrepancy->review_status === 'approved') {
                throw ValidationException::withMessages(['discrepancy' => 'تم اعتماد هذا الفرق مسبقًا.']);
            }
            if ($trip->status !== 'pending_review') {
                throw ValidationException::withMessages(['trip' => 'الرحلة ليست في مرحلة انتظار المراجعة.']);
            }

            $lockedDiscrepancy->update(['review_status' => 'approved', 'reviewed_by' => $reviewer->id, 'reviewed_at' => now()]);
            $trip->update(['status' => 'approved', 'approved_by' => $reviewer->id, 'approved_at' => now()]);
        });
    }
}
