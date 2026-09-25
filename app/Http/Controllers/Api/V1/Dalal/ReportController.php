<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Controller;
use App\Services\Dalal\DalalReports;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * التقارير الأربعة ببنية واحدة (أعمدة، سطور، مجاميع) — التطبيق يعرضها أو يصدّرها.
 */
class ReportController extends Controller
{
    public function show(Request $request, string $type, DalalReports $reports): JsonResponse
    {
        $report = $reports->build($request->user(), $type, $request->only(['from', 'to', 'status', 'payment_status_id', 'species_id', 'owner_id']));

        // الفلاتر والمجاميع كائنان في JSON حتى حين يكونان فارغين.
        return response()->json(['data' => ['filters' => (object) $report['filters'], 'totals' => (object) $report['totals']] + $report]);
    }
}
