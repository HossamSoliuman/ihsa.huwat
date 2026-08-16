<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Trip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FieldStatisticsController extends Controller
{
    public function index(Request $request): View
    {
        $queue = Trip::with(['boat', 'departurePort'])
            ->whereIn('status', ['عادت للميناء', 'بانتظار الإحصاء', 'تحت الإحصاء', 'بانتظار الاعتماد'])
            ->orderBy('return_time')
            ->get();

        $filtered = $request->filled('status')
            ? $queue->where('status', $request->query('status'))->values()
            : $queue;

        $stats = [
            'returned' => $queue->where('status', 'عادت للميناء')->count(),
            'pending' => $queue->where('status', 'بانتظار الإحصاء')->count(),
            'under' => $queue->where('status', 'تحت الإحصاء')->count(),
            'awaiting' => $queue->where('status', 'بانتظار الاعتماد')->count(),
            'declared' => $queue->sum('captain_input_kg'),
            'measured' => $queue->sum('actual_weight_kg'),
        ];

        return view('field-statistics.index', ['trips' => $filtered, 'stats' => $stats]);
    }

    public function record(Request $request, Trip $trip): RedirectResponse
    {
        $data = $request->validate([
            'actual_weight_kg' => ['required', 'numeric', 'min:0'],
            'statistics_officer' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['diff_kg'] = round($data['actual_weight_kg'] - (float) $trip->captain_input_kg, 2);
        $data['status'] = 'بانتظار الاعتماد';
        $trip->update($data);

        AuditLog::create([
            'action' => 'إحصاء',
            'entity' => 'Trip',
            'record_label' => $trip->trip_number,
            'details' => "تسجيل الوزن الفعلي {$data['actual_weight_kg']} كجم بفرق {$data['diff_kg']} كجم",
        ]);

        return redirect()->route('gov.field-statistics')->with('status', "تم تسجيل إحصاء الرحلة {$trip->trip_number}");
    }
}