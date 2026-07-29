<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteHarborRecordRequest;
use App\Http\Requests\StoreHarborWorkerRequest;
use App\Models\HarborWorker;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class HarborWorkerController extends Controller
{
    public function store(StoreHarborWorkerRequest $request, Port $port): RedirectResponse
    {
        $attributes = $this->attributes($request, null);
        $port->harborWorkers()->create($attributes);

        return back()->with('status', 'تمت إضافة سجل القوى البشرية.');
    }

    public function update(StoreHarborWorkerRequest $request, Port $port, HarborWorker $harborWorker): RedirectResponse
    {
        $this->ensureBelongsToPort($harborWorker, $port);
        $harborWorker->update($this->attributes($request, $harborWorker));

        return back()->with('status', 'تم تحديث سجل القوى البشرية.');
    }

    public function destroy(DeleteHarborRecordRequest $request, Port $port, HarborWorker $harborWorker): RedirectResponse
    {
        $this->ensureBelongsToPort($harborWorker, $port);
        $harborWorker->delete();

        return back()->with('status', 'تم حذف سجل العامل.');
    }

    private function attributes(StoreHarborWorkerRequest $request, ?HarborWorker $worker): array
    {
        $attributes = $request->validated();
        $identity = $attributes['identity_number'] ?? null;
        $attributes['identity_number'] = filled($identity) ? Hash::make($identity) : $worker?->identity_number;

        return $attributes;
    }

    private function ensureBelongsToPort(HarborWorker $worker, Port $port): void
    {
        abort_unless($worker->port_id === $port->id, 404);
    }
}
