<?php

namespace App\Actions;

use App\Models\InformationSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReviewInformationSubmissionAction
{
    public function execute(InformationSubmission $submission, string $targetStatus, ?string $note, User $actor): InformationSubmission
    {
        return DB::transaction(function () use ($submission, $targetStatus, $note, $actor): InformationSubmission {
            $lockedSubmission = InformationSubmission::query()->lockForUpdate()->findOrFail($submission->getKey());
            $fromStatus = $lockedSubmission->status;

            if ($targetStatus !== $fromStatus && ! in_array($targetStatus, InformationSubmission::TRANSITIONS[$fromStatus] ?? [], true)) {
                throw ValidationException::withMessages(['status' => 'الانتقال المطلوب غير مسموح من حالة الطلب الحالية.']);
            }

            if ($targetStatus === 'rejected' && blank($note)) {
                throw ValidationException::withMessages(['review_notes' => 'يجب إضافة سبب الرفض قبل رفض الطلب.']);
            }

            if ($targetStatus === 'needs_edit' && blank($note)) {
                throw ValidationException::withMessages(['review_notes' => 'يجب توضيح التعديلات المطلوبة قبل إعادة الطلب لمقدّمه.']);
            }

            $lockedSubmission->forceFill([
                'status' => $targetStatus,
                'review_notes' => filled($note) ? $note : null,
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
            ])->save();

            $lockedSubmission->events()->create([
                'event_type' => $targetStatus === $fromStatus ? 'note_updated' : 'status_changed',
                'from_status' => $fromStatus,
                'to_status' => $targetStatus,
                'note' => filled($note) ? $note : null,
                'actor_user_id' => $actor->getKey(),
            ]);

            return $lockedSubmission;
        });
    }
}
