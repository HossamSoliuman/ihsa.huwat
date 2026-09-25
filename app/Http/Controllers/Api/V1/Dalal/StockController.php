<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Controller;
use App\Services\Dalal\DalalStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * شاشة "المخزون": البطاقات، ثم المخزون مجمّعًا حسب المالك بدفعاته، ثم
 * المتاح لكل صنف (قائمة نوع السمك في شاشة البيع).
 */
class StockController extends Controller
{
    public function index(Request $request, DalalStock $stock): JsonResponse
    {
        $dalal = $request->user();

        return response()->json(['data' => [
            'summary' => $stock->summary($dalal),
            'owners' => $stock->byOwner($dalal),
            'species' => $stock->bySpecies($dalal),
        ]]);
    }
}
