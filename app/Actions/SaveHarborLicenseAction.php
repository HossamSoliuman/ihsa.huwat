<?php

namespace App\Actions;

use App\Models\HarborLicense;
use App\Models\Port;
use Illuminate\Support\Arr;
use Throwable;

class SaveHarborLicenseAction
{
    public function __construct(private SaveHarborAttachmentAction $attachments) {}

    public function execute(Port $port, ?HarborLicense $license, array $attributes): HarborLicense
    {
        $oldPath = $license?->attachment_path;
        $newPath = $this->attachments->store($attributes['attachment'] ?? null, 'licenses', $oldPath, $attributes['remove_attachment'] ?? false);

        try {
            $license ??= new HarborLicense(['port_id' => $port->id]);
            $license->fill([...Arr::except($attributes, ['attachment', 'remove_attachment']), 'attachment_path' => $newPath])->save();
        } catch (Throwable $exception) {
            if ($newPath !== $oldPath) {
                $this->attachments->delete($newPath);
            }
            throw $exception;
        }

        if ($newPath !== $oldPath) {
            $this->attachments->delete($oldPath);
        }

        return $license;
    }
}
