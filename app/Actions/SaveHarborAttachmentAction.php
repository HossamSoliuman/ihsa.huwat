<?php

namespace App\Actions;

use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SaveHarborAttachmentAction
{
    public function __construct(private FilesystemManager $filesystem) {}

    public function store(?UploadedFile $upload, string $category, ?string $existingPath, bool $remove): ?string
    {
        if ($upload === null) {
            return $remove ? null : $existingPath;
        }

        return $this->filesystem->disk('local')->putFileAs(
            'harbor/'.$category,
            $upload,
            Str::uuid().'.'.$upload->extension(),
        );
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            $this->filesystem->disk('local')->delete($path);
        }
    }
}
