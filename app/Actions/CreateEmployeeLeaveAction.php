<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateEmployeeLeaveAction
{
    public function execute(User $user, array $attributes): Leave
    {
        return DB::transaction(function () use ($user, $attributes): Leave {
            $employee = Employee::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            $overlaps = $employee->leaves()->whereIn('status', ['pending', 'approved'])
                ->whereDate('start_date', '<=', $attributes['end_date'])
                ->whereDate('end_date', '>=', $attributes['start_date'])->exists();

            if ($overlaps) {
                throw ValidationException::withMessages(['start_date' => 'يوجد طلب إجازة قائم يتداخل مع هذه الفترة.']);
            }

            return $employee->leaves()->create([...$attributes, 'status' => 'pending']);
        });
    }
}
