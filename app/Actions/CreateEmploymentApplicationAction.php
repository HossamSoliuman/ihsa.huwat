<?php

namespace App\Actions;

use App\Models\EmploymentApplication;
use App\Models\EmploymentJob;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CreateEmploymentApplicationAction
{
    public function __construct(private FilesystemManager $filesystem) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{file: UploadedFile, type: string}>  $uploads
     */
    public function handle(EmploymentJob $job, array $attributes, array $uploads): EmploymentApplication
    {
        $storedPaths = [];

        try {
            return DB::transaction(function () use ($job, $attributes, $uploads, &$storedPaths): EmploymentApplication {
                $openJob = EmploymentJob::query()->open()->lockForUpdate()->find($job->getKey());

                if (! $openJob) {
                    throw ValidationException::withMessages([
                        'general' => 'انتهت فترة التقديم على هذه الوظيفة.',
                    ]);
                }

                $application = $openJob->applications()->create([
                    ...$attributes,
                    'reference_no' => $this->newReference(),
                    'status' => EmploymentApplication::STATUS_SUBMITTED,
                    'submitted_at' => now(),
                ]);

                foreach ($uploads as $upload) {
                    $path = $this->storeAttachment($application, $upload['file'], $upload['type']);
                    $storedPaths[] = $path;
                }

                $application->events()->create([
                    'event_type' => 'submitted',
                    'to_status' => EmploymentApplication::STATUS_SUBMITTED,
                    'note' => 'تم إرسال الطلب عبر بوابة التوظيف العامة.',
                ]);

                return $application;
            });
        } catch (Throwable $exception) {
            $this->filesystem->disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    private function newReference(): string
    {
        do {
            $reference = 'APP-'.Str::upper(bin2hex(random_bytes(12)));
        } while (EmploymentApplication::query()->where('reference_no', $reference)->exists());

        return $reference;
    }

    private function storeAttachment(EmploymentApplication $application, UploadedFile $file, string $type): string
    {
        $path = $this->filesystem->disk('local')->putFileAs(
            'employment/'.$application->reference_no,
            $file,
            Str::uuid().'.'.$file->extension(),
        );

        $application->attachments()->create([
            'attachment_type' => $type,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'file_size' => $file->getSize(),
        ]);

        return $path;
    }
}
