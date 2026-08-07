<?php

namespace Database\Factories;

use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishMarketBroker> */
class FishMarketBrokerFactory extends Factory
{
    /** A منشأة by default; `individual()` files the same broker as a فرد instead. */
    public function definition(): array
    {
        return [
            'fish_market_id' => FishMarket::factory(),
            'entity_type' => FishMarketBroker::TYPE_ESTABLISHMENT,
            'full_name' => null,
            'nationality' => null,
            'phone' => '05'.fake()->numerify('########'),
            'job_title' => null,
            'entity_name' => 'مؤسسة '.fake()->unique()->lastName().' للدلالة',
            'commercial_registration_no' => fake()->unique()->numerify('10########'),
            'email' => fake()->unique()->safeEmail(),
            'tax_number' => fake()->numerify('3###########03'),
            'national_address' => fake()->streetName().' — '.fake()->city(),
            'is_active' => true,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (): array => [
            'entity_type' => FishMarketBroker::TYPE_INDIVIDUAL,
            'full_name' => fake()->name(),
            'nationality' => array_key_first((array) config('information.nationalities')),
            'job_title' => array_key_first((array) config('information.market_job_titles')),
            'entity_name' => null,
            'commercial_registration_no' => null,
            'email' => null,
            'tax_number' => null,
            'national_address' => null,
        ]);
    }
}
