<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Services\Dalal\DalalStock;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مخزون الدلال: ما استلمه من الملاك ولم يُبع بعد، مجمّعًا حسب المالك، ثم
 * سجلّ الإرسالات الواردة.
 */
class StockController extends Controller
{
    public function index(Request $request, DalalStock $stock): View
    {
        $dalal = $request->user();

        return view('panel.dalal.stock.index', [
            'summary' => $stock->summary($dalal),
            'owners' => $stock->byOwner($dalal),
            'consignments' => Consignment::forDalal($dalal)->with(['owner:id,name', 'trip:id,trip_number', 'items.species:id,name_ar'])
                ->latest('sent_at')->paginate(15)->withQueryString(),
        ]);
    }
}
