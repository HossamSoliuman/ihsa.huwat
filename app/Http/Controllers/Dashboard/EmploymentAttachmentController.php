<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EmploymentApplicationAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmploymentAttachmentController extends Controller
{
    public function __invoke(EmploymentApplicationAttachment $attachment): StreamedResponse
    {
        $attachment->load('application');
        Gate::authorize('view', $attachment->application);

        abort_unless(Storage::disk('local')->exists($attachment->stored_path), 404);

        return Storage::disk('local')->download(
            $attachment->stored_path,
            basename(str_replace('\\', '/', $attachment->original_name)),
            [
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Type' => in_array($attachment->mime_type, ['application/pdf', 'image/jpeg', 'image/png'], true)
                    ? $attachment->mime_type
                    : 'application/octet-stream',
                'Content-Security-Policy' => 'sandbox',
                'X-Content-Type-Options' => 'nosniff',
                'X-Download-Options' => 'noopen',
            ],
        );
    }
}
