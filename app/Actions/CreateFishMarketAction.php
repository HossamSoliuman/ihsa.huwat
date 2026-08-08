<?php

namespace App\Actions;

use App\Models\FishMarket;
use App\Models\FishMarketUnit;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Opens a market. The desk knows how many محلات ودكات the market holds long before it knows
 * whose they are, so the two counts lay out that many blank records — numbered محل ١، محل ٢
 * and so on — and the market page fills each one in afterwards.
 */
class CreateFishMarketAction
{
    /** @param  array<string, mixed>  $attributes */
    public function execute(array $attributes): FishMarket
    {
        $shops = (int) ($attributes['shops_count'] ?? 0);
        $auctionStalls = (int) ($attributes['auction_stalls_count'] ?? 0);

        /** The counts open the market; they are not columns on it. */
        $attributes = Arr::except($attributes, ['shops_count', 'auction_stalls_count']);

        return DB::transaction(function () use ($attributes, $shops, $auctionStalls): FishMarket {
            $market = FishMarket::query()->create($attributes);

            $this->layOutUnits($market, FishMarketUnit::TYPE_SHOP, $shops);
            $this->layOutUnits($market, FishMarketUnit::TYPE_AUCTION_STALL, $auctionStalls);

            return $market;
        });
    }

    /** Blank but numbered, so the market page shows the row waiting for its details. */
    private function layOutUnits(FishMarket $market, string $type, int $count): void
    {
        if ($count < 1) {
            return;
        }

        $prefix = $type === FishMarketUnit::TYPE_SHOP ? 'محل' : 'دكة';

        $market->units()->createMany(
            collect(range(1, $count))
                ->map(fn (int $number): array => [
                    'unit_type' => $type,
                    'label' => $prefix.' '.$number,
                ])
                ->all(),
        );
    }
}
