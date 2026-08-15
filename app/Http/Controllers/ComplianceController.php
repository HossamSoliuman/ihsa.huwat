<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\Violation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplianceController extends Controller
{
    public function index(Request $request): View
    {
        $violations = Violation::with('boat')->orderByDesc('date')->get();

        $filtered = $violations
            ->when($request->filled('type'), fn ($c) => $c->where('violation_type', $request->query('type')))
            ->when($request->filled('severity'), fn ($c) => $c->where('severity', $request->query('severity')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $stats = [
            'total' => $violations->count(),
            'open' => $violations->whereNotIn('status', ['مغلقة', 'تم الإجراء'])->count(),
            'high' => $violations->whereIn('severity', ['مرتفع', 'حرج'])->count(),
            'fines' => $violations->sum('fine_amount'),
            'boats' => $violations->pluck('boat_id')->filter()->unique()->count(),
        ];

        return view('compliance.index', [
            'violations' => $filtered,
            'stats' => $stats,
            'types' => $violations->pluck('violation_type')->unique()->filter()->values(),
            'boats' => Boat::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Violation::create($request->validate([
            'boat_id' => ['nullable', 'exists:boats,id'],
            'violation_type' => ['required', 'string', 'max:255'],
            'severity' => ['required', 'in:منخفض,متوسط,مرتفع,حرج'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'fine_amount' => ['nullable', 'numeric', 'min:0'],
            'action' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:255'],
        ]));

        return redirect()->route('compliance')->with('status', 'تم تسجيل المخالفة بنجاح');
    }
}