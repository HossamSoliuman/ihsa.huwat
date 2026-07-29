<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\SaveHarborAttachmentAction;
use App\Actions\SaveHarborViolationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteHarborRecordRequest;
use App\Http\Requests\StoreHarborViolationRequest;
use App\Models\HarborViolation;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;

class HarborViolationController extends Controller
{
    public function store(StoreHarborViolationRequest $request, Port $port, SaveHarborViolationAction $action): RedirectResponse
    {
        $action->execute($port, null, $request->validated(), $request->user());

        return back()->with('status', 'تم تسجيل المخالفة.');
    }

    public function update(StoreHarborViolationRequest $request, Port $port, HarborViolation $harborViolation, SaveHarborViolationAction $action): RedirectResponse
    {
        $this->ensureBelongsToPort($harborViolation, $port);
        $action->execute($port, $harborViolation, $request->validated(), $request->user());

        return back()->with('status', 'تم تحديث بيانات المخالفة.');
    }

    public function destroy(DeleteHarborRecordRequest $request, Port $port, HarborViolation $harborViolation, SaveHarborAttachmentAction $attachments): RedirectResponse
    {
        $this->ensureBelongsToPort($harborViolation, $port);
        $path = $harborViolation->attachment_path;
        $harborViolation->delete();
        $attachments->delete($path);

        return back()->with('status', 'تم حذف المخالفة ومرفقها.');
    }

    private function ensureBelongsToPort(HarborViolation $violation, Port $port): void
    {
        abort_unless($violation->port_id === $port->id, 404);
    }
}
