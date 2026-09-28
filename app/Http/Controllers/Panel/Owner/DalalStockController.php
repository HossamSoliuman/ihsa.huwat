<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\User;
use App\Services\Owner\DalalSettlement;
use App\Services\Owner\OwnerDalalStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مخزون الدلالين (O5): ما أرسله المالك إلى كل دلال وما بيع منه وما بقي عنده،
 * حسب القارب ثم الرحلة، وتفاصيل رحلة بدلاليها وأصنافها وإرسالاتها وفواتيرها.
 */
class DalalStockController extends Controller
{
    use ResolvesOwnerRecords;

    public function __construct(private readonly OwnerDalalStock $stock) {}

    public function index(Request $request, DalalSettlement $settlement): View
    {
        $owner = $request->user();
        $rows = $this->stock->rows($owner, [
            'boat_id' => $request->query('boat_id'),
            'dalal_id' => $request->query('dalal_id'),
            'holding' => $request->boolean('holding'),
        ]);

        return view('panel.owner.dalal-stock.index', [
            'totals' => $this->stock->totals($rows),
            'boats' => $this->stock->byBoat($rows),
            'trips' => $this->stock->byTrip($rows),
            'dalals' => $this->stock->byDalal($rows),
            'boatOptions' => Boat::forOwner($owner)->orderBy('name')->pluck('name', 'id'),
            'dalalOptions' => User::whereIn('id', $settlement->dalalIds($owner))->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function trip(Request $request, int $trip): View
    {
        $owner = $request->user();
        $model = $this->ownedTrip($owner, $trip)->load('boat');
        $rows = $this->stock->rows($owner, ['trip_id' => $model->id]);

        return view('panel.owner.dalal-stock.trip', [
            'trip' => $model,
            'totals' => $this->stock->totals($rows),
            'dalals' => $this->stock->byDalal($rows),
        ] + $this->stock->tripActivity($owner, $model));
    }
}
