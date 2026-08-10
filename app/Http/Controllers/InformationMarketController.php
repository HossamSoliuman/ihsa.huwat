<?php

namespace App\Http\Controllers;

use App\Actions\CreateFishMarketAction;
use App\Actions\DeleteMasterDataRecordAction;
use App\Actions\Information\Support\InformationScope;
use App\Http\Requests\FilterFishMarketsRequest;
use App\Http\Requests\ManageFishMarketRequest;
use App\Http\Requests\StoreFishMarketRequest;
use App\Http\Requests\UpdateFishMarketRequest;
use App\Models\FishMarket;
use App\Models\Governorate;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;

/**
 * أسواق السمك. A market carries its investor inline and hosts two kinds of unit —
 * محلات بيع السمك and دكات الحراجات — each with its own عمالة. The create screen asks only
 * for the market's location, name and how many of each unit it holds; the investor and the
 * details of every unit are completed from the market page, so a mistake in one record
 * never discards the whole tree.
 */
class InformationMarketController extends Controller
{
    /** Every dependant that has to be clear before a market may be deleted. */
    private const DEPENDENCIES = [
        'fish_market_units' => 'fish_market_id',
        'fish_market_brokers' => 'fish_market_id',
    ];

    public function index(FilterFishMarketsRequest $request): View
    {
        $filters = $request->validated();
        $scope = $request->user()->informationScope();

        $markets = $scope->applyMarkets(FishMarket::query())
            ->with('governorate.region')
            ->withCount(['shops', 'auctionStalls', 'workers', 'brokers'])
            ->when($filters['region_id'] ?? null, fn (Builder $query, int $regionId): Builder => $query->whereIn(
                'governorate_id',
                Governorate::query()->where('region_id', $regionId)->select('id'),
            ))
            ->when($filters['governorate_id'] ?? null, fn (Builder $query, int $governorateId): Builder => $query
                ->where('governorate_id', $governorateId))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query
                ->where('is_active', $status === 'active'))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('investor_name', 'like', "%{$search}%")
                        ->orWhere('investor_commercial_registration_no', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('information.admin.markets.index', [
            'markets' => $markets,
            'filters' => $filters,
            ...$this->geography($scope),
        ]);
    }

    public function create(ManageFishMarketRequest $request): View
    {
        $scope = $request->user()->informationScope();
        abort_unless($scope->allowsNewMarkets(), 403);

        return view('information.admin.markets.create', $this->geography($scope));
    }

    public function store(StoreFishMarketRequest $request, CreateFishMarketAction $createFishMarket): RedirectResponse
    {
        abort_unless($request->user()->informationScope()->allowsNewMarkets(), 403);

        $market = $createFishMarket->execute($request->validated());

        return to_route('information.admin.markets.show', $market)
            ->with('status', 'تم إنشاء السوق. أكمل بيانات المحلات ودكات الحراجات وعمالتها من هذه الصفحة.');
    }

    public function show(ManageFishMarketRequest $request, FishMarket $market): View
    {
        /** One load for the whole tree: lazy loading is switched off outside production. */
        $market->load([
            'governorate.region',
            'units' => fn ($query) => $query->with('workers')->orderBy('label')->orderBy('id'),
            'brokers' => fn ($query) => $query->ordered(),
        ]);

        return view('information.admin.markets.show', [
            'market' => $market,
            /** Pickable values for the selects; every value ever offered for reading rows back. */
            'nationalities' => Nationality::options(),
            'jobTitles' => MarketJobTitle::options(),
            'nationalityLabels' => Nationality::labels(),
            'jobTitleLabels' => MarketJobTitle::labels(),
            ...$this->geography($request->user()->informationScope()),
        ]);
    }

    public function update(UpdateFishMarketRequest $request, FishMarket $market): RedirectResponse
    {
        $market->update($request->validated());

        return to_route('information.admin.markets.show', $market)->with('status', 'تم تحديث بيانات السوق.');
    }

    public function destroy(
        ManageFishMarketRequest $request,
        FishMarket $market,
        DeleteMasterDataRecordAction $deleteMasterDataRecord,
    ): RedirectResponse {
        abort_unless($request->user()->informationScope()->allowsNewMarkets(), 403);

        /** The foreign keys cascade, so the guard is what keeps a populated market whole. */
        $deleteMasterDataRecord->execute($market, self::DEPENDENCIES, 'السوق');

        return to_route('information.admin.markets.index')->with('status', 'تم حذف السوق.');
    }

    /**
     * The region picker in front of the governorate list, as the ports desk does it, holding
     * only the geography the account reaches.
     *
     * @return array<string, mixed>
     */
    private function geography(InformationScope $scope): array
    {
        return [
            'regions' => $scope->applyRegions(Region::query())->orderBy('name')->get(['id', 'name']),
            'governorates' => $scope->applyGovernorates(Governorate::query())->orderBy('name')->get(['id', 'region_id', 'name']),
        ];
    }
}
