<?php

namespace App\Actions;

use App\Models\EmploymentJob;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionEmploymentJobAction
{
    public function execute(EmploymentJob $job, string $transition, User $actor): EmploymentJob
    {
        return DB::transaction(function () use ($job, $transition, $actor): EmploymentJob {
            $lockedJob = EmploymentJob::query()->lockForUpdate()->findOrFail($job->id);
            $targetStatus = match ($transition) {
                'publish' => $this->publish($lockedJob),
                'close' => $this->from($lockedJob, ['open'], 'closed', 'يمكن إغلاق الفرص المتاحة فقط.'),
                'archive' => $this->archive($lockedJob),
                'restore' => $this->from($lockedJob, ['archived'], 'draft', 'يمكن استعادة الفرص المؤرشفة فقط.'),
            };

            $lockedJob->forceFill([
                'status' => $targetStatus,
                'updated_by' => $actor->id,
            ])->save();

            return $lockedJob;
        });
    }

    private function publish(EmploymentJob $job): string
    {
        $this->from($job, ['draft', 'closed'], 'open', 'لا يمكن نشر هذه الفرصة من حالتها الحالية.');

        if ($job->application_deadline?->isBefore(today())) {
            throw ValidationException::withMessages([
                'application_deadline' => 'حدّث موعد إغلاق التقديم قبل نشر الفرصة.',
            ]);
        }

        $job->published_at ??= now();

        return 'open';
    }

    private function archive(EmploymentJob $job): string
    {
        if ($job->status === 'archived') {
            throw ValidationException::withMessages(['transition' => 'الفرصة مؤرشفة بالفعل.']);
        }

        return 'archived';
    }

    private function from(EmploymentJob $job, array $allowed, string $target, string $message): string
    {
        if (! in_array($job->status, $allowed, true)) {
            throw ValidationException::withMessages(['transition' => $message]);
        }

        return $target;
    }
}
