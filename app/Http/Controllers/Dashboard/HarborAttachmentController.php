<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\ViewHarborRequest;
use App\Models\HarborLicense;
use App\Models\HarborViolation;
use App\Models\Port;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HarborAttachmentController extends Controller
{
    public function license(ViewHarborRequest $request, Port $port, HarborLicense $harborLicense): StreamedResponse
    {
        abort_unless($harborLicense->port_id === $port->id, 404);

        return $this->download($harborLicense->attachment_path, 'license-'.$harborLicense->license_number);
    }

    public function violation(ViewHarborRequest $request, Port $port, HarborViolation $harborViolation): StreamedResponse
    {
        abort_unless($harborViolation->port_id === $port->id, 404);

        return $this->download($harborViolation->attachment_path, 'violation-'.$harborViolation->violation_number);
    }

    private function download(?string $path, string $name): StreamedResponse
    {
        abort_unless($path !== null && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $name.'.'.pathinfo($path, PATHINFO_EXTENSION), [
            'Cache-Control' => 'private, no-store, max-age=0', 'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff', 'X-Download-Options' => 'noopen',
        ]);
    }
}
