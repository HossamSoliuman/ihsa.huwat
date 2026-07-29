<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\BuildHarborWorkspaceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHarborRequest;
use App\Http\Requests\UpdateHarborRequest;
use App\Http\Requests\ViewHarborRequest;
use App\Models\Governorate;
use App\Models\Port;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class HarborController extends Controller
{
    public function index(ViewHarborRequest $request, BuildHarborWorkspaceAction $action): View
    {
        return $this->workspace($request, null, $action);
    }

    public function show(ViewHarborRequest $request, Port $port, BuildHarborWorkspaceAction $action): View
    {
        return $this->workspace($request, $port, $action);
    }

    public function store(StoreHarborRequest $request): RedirectResponse
    {
        $port = Port::query()->create($request->validated());

        return to_route('dashboard.harbors.show', $port)->with('status', 'تم إنشاء المرفأ وإتاحته لاستكمال بياناته.');
    }

    public function update(UpdateHarborRequest $request, Port $port): RedirectResponse
    {
        $port->update($request->validated());

        return back()->with('status', 'تم تحديث جميع بيانات المرفأ.');
    }

    private function workspace(ViewHarborRequest $request, ?Port $port, BuildHarborWorkspaceAction $action): View
    {
        $data = $action->execute($request->user(), $port);
        $data['governorates'] = Governorate::query()->with('region')
            ->when($request->user()->role->code === 'region_manager', fn ($query) => $query->where('region_id', $request->user()->region_id))
            ->when(in_array($request->user()->role->code, ['gov_supervisor', 'port_supervisor'], true), fn ($query) => $query->whereKey($data['harbor']?->governorate_id ?? $request->user()->governorate_id))
            ->orderBy('name')->get();
        $data['tab'] = $request->validated('tab', 'overview');

        return view('dashboard.harbors.show', $data);
    }
}
