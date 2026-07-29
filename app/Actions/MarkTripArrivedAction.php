<?php

namespace App\Actions;

use App\Models\Trip;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarkTripArrivedAction
{
    public function execute(Trip $trip): Trip
    {
        return DB::transaction(function () use ($trip): Trip {
            $lockedTrip = Trip::query()->lockForUpdate()->findOrFail($trip->id);
            if ($lockedTrip->status !== 'expected') {
                throw ValidationException::withMessages(['trip' => 'يمكن تسجيل الوصول للرحلات المتوقعة فقط.']);
            }

            $lockedTrip->update(['status' => 'arrived', 'actual_arrival' => now()]);

            return $lockedTrip;
        });
    }
}
