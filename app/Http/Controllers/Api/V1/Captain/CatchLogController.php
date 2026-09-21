<?php

namespace App\Http\Controllers\Api\V1\Captain;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CatchLogEntryResource;
use App\Services\Captain\CatchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * سجل الصيد: السطور مصفّحة، والملخص بالصنف في ردّ مستقل.
 */
class CatchLogController extends Controller
{
    public function index(Request $request, CatchLog $log): AnonymousResourceCollection
    {
        return CatchLogEntryResource::collection(
            $log->entries($request->user(), $request->query('search'), min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function summary(Request $request, CatchLog $log): JsonResponse
    {
        $summary = $log->summary($request->user());

        return response()->json(['data' => [
            'totals' => [
                'captain_kg' => round((float) $summary->sum('captain_kg'), 2),
                'counted_kg' => round((float) $summary->sum('counted_kg'), 2),
                'species' => $summary->count(),
            ],
            'by_species' => $summary->values(),
        ]]);
    }
}
