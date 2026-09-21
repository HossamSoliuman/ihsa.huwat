<?php

namespace App\Http\Controllers\Panel\Captain;

use App\Http\Controllers\Controller;
use App\Services\Captain\CatchLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * سجل الصيد: ما أعلنه الكابتن من مصيد على رحلاته وما عُدّ منه.
 */
class CatchLogController extends Controller
{
    public function index(Request $request, CatchLog $log): View
    {
        $summary = $log->summary($request->user());

        return view('panel.captain.catch-log', [
            'entries' => $log->entries($request->user(), $request->query('search')),
            'summary' => $summary,
            'totals' => [
                'captain_kg' => round((float) $summary->sum('captain_kg'), 2),
                'counted_kg' => round((float) $summary->sum('counted_kg'), 2),
                'species' => $summary->count(),
            ],
        ]);
    }
}
