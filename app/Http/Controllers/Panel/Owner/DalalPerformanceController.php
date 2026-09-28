<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\DalalPerformance;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * أداء الدلالين (O5): المؤشرات والمخططات على فترة، وجدول المقارنة.
 */
class DalalPerformanceController extends Controller
{
    public function index(Request $request, DalalPerformance $performance): View
    {
        return view('panel.owner.dalal-performance.index', $performance->for(
            $request->user(),
            (string) $request->query('period', 'quarter'),
            $request->query('from'),
            $request->query('to'),
            $request->integer('species_id') ?: null,
        ));
    }
}
