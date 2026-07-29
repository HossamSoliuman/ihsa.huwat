<?php

namespace App\Http\Controllers\Dashboard;

use App\Actions\UpdateHarborCapacitiesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateHarborCapacitiesRequest;
use App\Models\Port;
use Illuminate\Http\RedirectResponse;

class HarborCapacityController extends Controller
{
    public function update(UpdateHarborCapacitiesRequest $request, Port $port, UpdateHarborCapacitiesAction $action): RedirectResponse
    {
        $action->execute($port, $request->validated('capacities'));

        return back()->with('status', 'تم تحديث الطاقة الاستيعابية وحالة الأرصفة.');
    }
}
