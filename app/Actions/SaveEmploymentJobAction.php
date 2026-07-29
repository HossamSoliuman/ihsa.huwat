<?php

namespace App\Actions;

use App\Models\EmploymentJob;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveEmploymentJobAction
{
    public function execute(?EmploymentJob $job, array $attributes, User $actor): EmploymentJob
    {
        if ($job?->status === 'archived') {
            throw ValidationException::withMessages([
                'job' => 'استعد الفرصة من الأرشيف قبل تعديل بياناتها.',
            ]);
        }

        if ($job === null) {
            $job = new EmploymentJob([
                'reference_no' => $this->uniqueReference(),
                'created_by' => $actor->id,
            ]);
        } else {
            $job->updated_by = $actor->id;
        }

        $job->fill($attributes)->save();

        return $job;
    }

    private function uniqueReference(): string
    {
        do {
            $reference = 'JOB-'.now()->year.'-'.Str::upper(Str::random(6));
        } while (EmploymentJob::query()->where('reference_no', $reference)->exists());

        return $reference;
    }
}
