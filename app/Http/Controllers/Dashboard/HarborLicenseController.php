<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveHarborAttachmentAction;
use App\Actions\SaveHarborLicenseAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteHarborRecordRequest;
use App\Http\Requests\StoreHarborLicenseRequest;
use App\Models\HarborLicense;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;

class HarborLicenseController extends Controller
{
    public function store(StoreHarborLicenseRequest $request, Port $port, SaveHarborLicenseAction $action): RedirectResponse
    {
        $action->execute($port, null, $request->validated());

        return back()->with('status', 'تم تسجيل الرخصة.');
    }

    public function update(StoreHarborLicenseRequest $request, Port $port, HarborLicense $harborLicense, SaveHarborLicenseAction $action): RedirectResponse
    {
        $this->ensureBelongsToPort($harborLicense, $port);
        $action->execute($port, $harborLicense, $request->validated());

        return back()->with('status', 'تم تحديث بيانات الرخصة.');
    }

    public function destroy(DeleteHarborRecordRequest $request, Port $port, HarborLicense $harborLicense, SaveHarborAttachmentAction $attachments): RedirectResponse
    {
        $this->ensureBelongsToPort($harborLicense, $port);
        $path = $harborLicense->attachment_path;
        $harborLicense->delete();
        $attachments->delete($path);

        return back()->with('status', 'تم حذف الرخصة ومرفقها.');
    }

    private function ensureBelongsToPort(HarborLicense $license, Port $port): void
    {
        abort_unless($license->port_id === $port->id, 404);
    }
}
