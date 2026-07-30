<?php

namespace App\Http\Controllers\Government;

use App\Actions\Government\BuildSeasonIndexAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Government\FilterSeasonsRequest;
use App\Http\Requests\Government\StoreSeasonRequest;
use App\Models\Region;
use App\Models\Season;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SeasonController extends Controller
{
    public function index(FilterSeasonsRequest $request, BuildSeasonIndexAction $buildSeasonIndex): View
    {
        return view('government.seasons.index', $buildSeasonIndex->handle($request->validated()));
    }

    public function create(): View
    {
        return view('government.seasons.create', [
            'regions' => Region::query()->select(['id', 'name'])->orderBy('name')->get(),
            'statuses' => config('government.season_statuses'),
            'fishingTools' => config('government.fishing_tool_options'),
        ]);
    }

    public function store(StoreSeasonRequest $request): RedirectResponse
    {
        Season::query()->create($request->validated());

        return to_route('government.seasons.index')->with('status', 'تم إنشاء موسم الصيد بنجاح.');
    }
}
