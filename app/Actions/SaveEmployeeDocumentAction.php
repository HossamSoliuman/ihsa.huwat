<?php

namespace App\Actions;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class SaveEmployeeDocumentAction
{
    public function __construct(
        private FilesystemManager $filesystem,
        private RecordAuditLogAction $recordAuditLog,
    ) {}

    public function execute(Employee $employee, array $attributes, UploadedFile $file, User $actor, ?string $ipAddress = null): EmployeeDocument
    {
        $path = $this->filesystem->disk('local')->putFileAs(
            'employment/documents/'.$employee->id,
            $file,
            Str::uuid().'.'.$file->extension(),
        );

        try {
            return DB::transaction(function () use ($employee, $attributes, $file, $actor, $ipAddress, $path): EmployeeDocument {
                $document = EmployeeDocument::query()->create([
                    ...$attributes,
                    'employee_id' => $employee->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_path' => $path,
                    'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                    'file_size' => $file->getSize(),
                    'uploaded_by' => $actor->id,
                ]);

                $this->recordAuditLog->execute(
                    $actor,
                    'employee_document_uploaded',
                    $document,
                    newValues: $document->only(['employee_id', 'document_type', 'document_number', 'original_name']),
                    ipAddress: $ipAddress,
                );

                return $document;
            });
        } catch (Throwable $exception) {
            $this->filesystem->disk('local')->delete($path);

            throw $exception;
        }
    }
}
