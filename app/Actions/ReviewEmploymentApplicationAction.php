<?php

namespace App\Actions;

use App\Models\EmploymentApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewEmploymentApplicationAction
{
    public function execute(EmploymentApplication $application, string $targetStatus, ?string $note, User $actor): EmploymentApplication
    {
        return DB::transaction(function () use ($application, $targetStatus, $note, $actor): EmploymentApplication {
            $lockedApplication = EmploymentApplication::query()->lockForUpdate()->findOrFail($application->id);
            $fromStatus = $lockedApplication->status;

            if ($fromStatus === 'account_created' || $lockedApplication->employee_user_id !== null) {
                throw ValidationException::withMessages(['status' => 'تم إنشاء حساب لهذا الموظف، ولا يمكن إعادة الطلب إلى مرحلة سابقة.']);
            }

            if ($fromStatus === 'withdrawn') {
                throw ValidationException::withMessages(['status' => 'الطلب منسحب ولا يمكن تغيير حالته.']);
            }

            if ($targetStatus !== $fromStatus && ! in_array($targetStatus, EmploymentApplication::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'الانتقال المطلوب غير مسموح من حالة الطلب الحالية.']);
            }

            $lockedApplication->forceFill([
                'status' => $targetStatus,
                'admin_note' => filled($note) ? $note : null,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'accepted_at' => $targetStatus === 'accepted' ? ($lockedApplication->accepted_at ?? now()) : null,
            ])->save();

            $lockedApplication->events()->create([
                'event_type' => $targetStatus === $fromStatus ? 'note_updated' : 'status_changed',
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'note' => filled($note) ? $note : null,
                'actor_user_id' => $actor->id,
            ]);

            return $lockedApplication;
        });
    }
}
