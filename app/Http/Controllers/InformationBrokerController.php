<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterFishMarketBrokersRequest;
use App\Http\Requests\ManageFishMarketRequest;
use App\Http\Requests\StoreFishMarketBrokerRequest;
use App\Http\Requests\UpdateFishMarketBrokerRequest;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\MarketJobTitle;
use App\Models\Nationality;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;

/**
 * الدلالين. Flat records attached to a market, filed either as a فرد or as a منشأة —
 * `entity_type` decides which fieldset applies, and the record keeps only that branch.
 */
class InformationBrokerController extends Controller
{
    public function index(FilterFishMarketBrokersRequest $request): View
    {
        $filters = $request->validated();

        $brokers = FishMarketBroker::query()
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
            'markets' => $this->markets(),
        ]);
    }

    public function create(ManageFishMarketRequest $request): View
    {
        return view('information.admin.brokers.create', [
            'markets' => $this->markets(),
            ...$this->options(),
        ]);
    }

    public function store(StoreFishMarketBrokerRequest $request): RedirectResponse
    {
        $broker = FishMarketBroker::query()->create($request->validated());

        return to_route('information.admin.brokers.show', $broker)->with('status', 'تمت إضافة الدلال.');
    }

    public function show(ManageFishMarketRequest $request, FishMarketBroker $broker): View
    {
        $broker->load('market.governorate.region');

        return view('information.admin.brokers.show', [
            'broker' => $broker,
            'markets' => $this->markets(),
            ...$this->options(),
        ]);
    }

    public function update(UpdateFishMarketBrokerRequest $request, FishMarketBroker $broker): RedirectResponse
    {
        $broker->update($request->validated());

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
    private function markets(): Collection
    {
        return FishMarket::query()->with('governorate')->ordered()->get(['id', 'governorate_id', 'name', 'is_active']);
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
