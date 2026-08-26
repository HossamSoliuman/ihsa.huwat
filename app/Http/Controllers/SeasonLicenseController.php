<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\FishingSeason;
use App\Models\SeasonLicense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeasonLicenseController extends Controller
{
    public function index(Request $request): View
    {
        $licenses = SeasonLicense::with(['fishingSeason', 'boat'])->latest()->get();

        $filtered = $licenses
            ->when($request->filled('season'), fn ($c) => $c->where('fishing_season_id', (int) $request->query('season')))
            ->when($request->filled('status'), fn ($c) => $c->where('status', $request->query('status')))
            ->values();

        $stats = [
            'total' => $licenses->where('status', '!=', 'ملغاة')->count(),
            'active' => $licenses->where('status', 'سارية')->count(),
            'stopped' => $licenses->where('status', 'موقوفة')->count(),
            'expired' => $licenses->where('status', 'منتهية')->count(),
            'boats' => $licenses->where('status', 'سارية')->pluck('boat_id')->unique()->count(),
        ];

        return view('season-licenses.index', [
            'licenses' => $filtered,
            'stats' => $stats,
            'seasons' => FishingSeason::orderBy('name')->get(),
            'boats' => Boat::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $license = SeasonLicense::create($this->validated($request));
        $this->syncSeasonCounts($license->fishing_season_id);

        return redirect()->route('services.season-licenses')->with('status', 'تم إصدار رخصة الموسم بنجاح');
    }

    public function update(Request $request, SeasonLicense $seasonLicense): RedirectResponse
    {
        $seasonLicense->update($this->validated($request));
        $this->syncSeasonCounts($seasonLicense->fishing_season_id);

        return redirect()->route('services.season-licenses')->with('status', 'تم تحديث رخصة الموسم بنجاح');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'license_number' => ['required', 'string', 'max:100'],
            'fishing_season_id' => ['required', 'exists:fishing_seasons,id'],
            'boat_id' => ['required', 'exists:boats,id'],
            'holder_name' => ['nullable', 'string', 'max:255'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'gear_type' => ['nullable', 'string', 'max:255'],
            'allowed_area' => ['nullable', 'string'],
            'status' => ['required', 'in:سارية,موقوفة,منتهية,ملغاة'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function syncSeasonCounts(int $seasonId): void
    {
        $season = FishingSeason::find($seasonId);
        $licenses = SeasonLicense::where('fishing_season_id', $seasonId)->get();

        $season?->update([
            'licenses_issued' => $licenses->where('status', '!=', 'ملغاة')->count(),
            'licenses_active' => $licenses->where('status', 'سارية')->count(),
        ]);
    }
}