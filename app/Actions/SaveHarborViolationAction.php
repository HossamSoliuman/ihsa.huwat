<?php

namespace App\Actions;

use App\Models\HarborViolation;
use App\Models\Port;
use App\Models\User;
use Illuminate\Support\Arr;
use Throwable;

class SaveHarborViolationAction
{
    public function __construct(private SaveHarborAttachmentAction $attachments) {}

    public function execute(Port $port, ?HarborViolation $violation, array $attributes, User $actor): HarborViolation
    {
        $oldPath = $violation?->attachment_path;
        $newPath = $this->attachments->store($attributes['attachment'] ?? null, 'violations', $oldPath, $attributes['remove_attachment'] ?? false);

        try {
            $violation ??= new HarborViolation(['port_id' => $port->id, 'created_by' => $actor->id]);
            $violation->fill([...Arr::except($attributes, ['attachment', 'remove_attachment']), 'attachment_path' => $newPath])->save();
        } catch (Throwable $exception) {
            if ($newPath !== $oldPath) {
                $this->attachments->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath !== $oldPath) {
            $this->attachments->delete($oldPath);
        }

        return $violation;
    }
}
