<?php

namespace App\Http\Controllers;

use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function index(Request $request): View
    {
        $regions = Region::orderBy('code')->get();

        $filtered = $regions->when($request->filled('search'), function ($c) use ($request) {
            $q = mb_strtolower($request->query('search'));
            return $c->filter(fn ($r) => str_contains(mb_strtolower($r->name.' '.$r->code), $q));
        })->values();

        $stats = [
            'total' => $regions->count(),
            'ports' => $regions->sum('ports_count'),
            'boats' => $regions->sum('active_boats'),
            'fishers' => $regions->sum('active_fishers'),
            'catch' => $regions->sum('total_catch_tons'),
            'coast' => $regions->sum('coast_length_km'),
        ];

        return view('regions.index', ['regions' => $filtered, 'stats' => $stats]);
    }

    public function store(Request $request): RedirectResponse
    {
        Region::create($this->validated($request));

        return redirect()->route('regions')->with('status', 'تم حفظ المنطقة بنجاح');
    }

    public function update(Request $request, Region $region): RedirectResponse
    {
        $region->update($this->validated($request));

        return redirect()->route('regions')->with('status', 'تم تحديث المنطقة بنجاح');
    }

    public function destroy(Region $region): RedirectResponse
    {
        $region->delete();

        return redirect()->route('regions')->with('status', 'تم حذف المنطقة');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20'],
            'coast_length_km' => ['nullable', 'numeric', 'min:0'],
            'governorates_count' => ['nullable', 'integer', 'min:0'],
            'ports_count' => ['nullable', 'integer', 'min:0'],
            'total_catch_tons' => ['nullable', 'numeric', 'min:0'],
            'active_boats' => ['nullable', 'integer', 'min:0'],
            'active_fishers' => ['nullable', 'integer', 'min:0'],
        ]);

        foreach (['coast_length_km', 'governorates_count', 'ports_count', 'total_catch_tons', 'active_boats', 'active_fishers'] as $field) {
            $data[$field] = $data[$field] ?? 0;
        }

        return $data;
    }
}