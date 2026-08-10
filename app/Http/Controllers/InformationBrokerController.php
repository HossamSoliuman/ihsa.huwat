<?php

namespace App\Http\Controllers;

use App\Actions\Information\Support\InformationScope;
use App\Http\Requests\FilterFishMarketBrokersRequest;
use App\Http\Requests\ManageFishMarketRequest;
use App\Http\Requests\StoreFishMarketBrokerRequest;
use App\Http\Requests\UpdateFishMarketBrokerRequest;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\FishMarketUnit;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * الدلالين. Flat records attached to a market, filed either as a فرد or as a منشأة —
 * `entity_type` decides which fieldset applies, and the record keeps only that branch.
 */
class InformationBrokerController extends Controller
{
    public function index(FilterFishMarketBrokersRequest $request): View
    {
        $filters = $request->validated();
        $scope = $request->user()->informationScope();

        $brokers = $scope->applyBrokers(FishMarketBroker::query())
            ->with('market.governorate')
            ->when($filters['fish_market_id'] ?? null, fn (Builder $query, int $marketId): Builder => $query
                ->where('fish_market_id', $marketId))
            ->when($filters['entity_type'] ?? null, fn (Builder $query, string $entityType): Builder => $query
                ->where('entity_type', $entityType))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query
                ->where('is_active', $status === 'active'))
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('full_name', 'like', "%{$search}%")
                        ->orWhere('entity_name', 'like', "%{$search}%")
                        ->orWhere('commercial_registration_no', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->paginate(20)
            ->withQueryString();

        return view('information.admin.brokers.index', [
            'brokers' => $brokers,
            'filters' => $filters,
            'markets' => $this->markets($scope),
        ]);
    }

    public function create(ManageFishMarketRequest $request): View
    {
        $scope = $request->user()->informationScope();

        return view('information.admin.brokers.create', [
            'markets' => $this->markets($scope),
            'stalls' => $this->stalls($scope),
            ...$this->options(),
        ]);
    }

    public function store(StoreFishMarketBrokerRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        $broker = DB::transaction(function () use ($attributes): FishMarketBroker {
            $broker = FishMarketBroker::query()->create(Arr::except($attributes, 'employees'));
            $this->saveEmployees($broker, $attributes['employees'] ?? []);

            return $broker;
        });

        return to_route('information.admin.brokers.show', $broker)->with('status', 'تمت إضافة الدلال.');
    }

    public function show(ManageFishMarketRequest $request, FishMarketBroker $broker): View
    {
        $broker->load('market.governorate.region', 'unit', 'employees');

        $scope = $request->user()->informationScope();

        return view('information.admin.brokers.show', [
            'broker' => $broker,
            'markets' => $this->markets($scope),
            'stalls' => $this->stalls($scope),
            ...$this->options(),
        ]);
    }

    public function update(UpdateFishMarketBrokerRequest $request, FishMarketBroker $broker): RedirectResponse
    {
        $attributes = $request->validated();

        DB::transaction(function () use ($broker, $attributes): void {
            $broker->update(Arr::except($attributes, 'employees'));
            $this->saveEmployees($broker, $attributes['employees'] ?? []);
        });

        return to_route('information.admin.brokers.show', $broker)->with('status', 'تم تحديث بيانات الدلال.');
    }

    public function destroy(ManageFishMarketRequest $request, FishMarketBroker $broker): RedirectResponse
    {
        $broker->delete();

        return to_route('information.admin.brokers.index')->with('status', 'تم حذف الدلال.');
    }

    /**
     * The السوق dropdown. Every market is offered, retired ones included, so a broker
     * already filed against one keeps rendering its name.
     *
     * @return Collection<int, FishMarket>
     */
    private function markets(InformationScope $scope): Collection
    {
        return $scope->applyMarkets(FishMarket::query())
            ->with('governorate')
            ->ordered()
            ->get(['id', 'governorate_id', 'name', 'is_active']);
    }

    /**
     * The دكة dropdown. Every market's stalls are offered at once and the page narrows them
     * to the market in play, so changing the market does not cost a round trip.
     *
     * @return Collection<int, FishMarketUnit>
     */
    private function stalls(InformationScope $scope): Collection
    {
        return FishMarketUnit::query()
            ->when(! $scope->isUnrestricted(), fn (Builder $query): Builder => $query
                ->whereIn('fish_market_id', $scope->marketIds()))
            ->where('unit_type', FishMarketUnit::TYPE_AUCTION_STALL)
            ->orderBy('fish_market_id')
            ->orderBy('id')
            ->get(['id', 'fish_market_id', 'label', 'entity_name']);
    }

    /**
     * The موظفون block is filed whole: the rows submitted replace whatever was on the
     * record, so dropping a row on the page removes it here.
     *
     * @param  list<array{job_title: string, nationality: string, headcount: int|string}>  $employees
     */
    private function saveEmployees(FishMarketBroker $broker, array $employees): void
    {
        $broker->employees()->delete();

        if ($employees !== []) {
            $broker->employees()->createMany($employees);
        }
    }

    /** @return array<string, array<string, string>> */
    private function options(): array
    {
        return [
            'nationalities' => Nationality::options(),
            'jobTitles' => MarketJobTitle::options(),
        ];
    }
}
