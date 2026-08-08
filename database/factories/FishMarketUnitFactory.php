<?php

namespace Database\Factories;

use App\Models\FishMarket;
use App\Models\FishMarketUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishMarketUnit> */
class FishMarketUnitFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'fish_market_id' => FishMarket::factory(),
            'unit_type' => FishMarketUnit::TYPE_SHOP,
            'label' => 'محل '.fake()->unique()->numberBetween(1, 400),
            'entity_name' => 'مؤسسة '.fake()->unique()->lastName().' التجارية',
            'commercial_registration_no' => fake()->unique()->numerify('10########'),
            'municipality_licence_no' => fake()->numerify('BL-######'),
            'national_address' => fake()->streetName().' — '.fake()->city(),
            'tax_number' => fake()->numerify('3###########03'),
            'is_active' => true,
        ];
    }

    public function shop(): static
    {
        return $this->state(fn (): array => [
            'unit_type' => FishMarketUnit::TYPE_SHOP,
            'label' => 'محل '.fake()->unique()->numberBetween(1, 400),
        ]);
    }

    public function auctionStall(): static
    {
        return $this->state(fn (): array => [
            'unit_type' => FishMarketUnit::TYPE_AUCTION_STALL,
            'label' => 'دكة '.fake()->unique()->numberBetween(1, 400),
        ]);
    }

    /** A record laid out by the market's opening counts, numbered but not yet filled in. */
    public function awaitingDetails(): static
    {
        return $this->state(fn (): array => [
            'entity_name' => null,
            'commercial_registration_no' => null,
            'municipality_licence_no' => null,
            'national_address' => null,
            'tax_number' => null,
        ]);
    }
}
