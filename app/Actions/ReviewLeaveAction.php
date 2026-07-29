<?php

namespace App\Actions;

use App\Models\Leave;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewLeaveAction
{
    public function execute(Leave $leave, string $decision, User $reviewer): Leave
    {
        return DB::transaction(function () use ($leave, $decision, $reviewer): Leave {
            $leave = Leave::query()->with('employee')->lockForUpdate()->findOrFail($leave->id);
            if ($leave->status !== 'pending') {
                throw ValidationException::withMessages(['leave' => 'تمت مراجعة الطلب بالفعل.']);
            }
            $leave->update(['status' => $decision, 'approved_by' => $reviewer->id]);
            if ($decision === 'approved' && today()->between($leave->start_date, $leave->end_date)) {
                $leave->employee->update(['status' => 'on_leave']);
            }

            return $leave;
        });
    }
}
