<?php

namespace Database\Factories;

use App\Models\FishMarketBroker;
use App\Models\FishMarketBrokerEmployee;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishMarketBrokerEmployee> */
class FishMarketBrokerEmployeeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'fish_market_broker_id' => FishMarketBroker::factory(),
            'job_title' => fake()->randomElement(array_keys((array) config('information.market_job_titles'))),
            'nationality' => fake()->randomElement(array_keys((array) config('information.nationalities'))),
            'headcount' => fake()->numberBetween(1, 20),
        ];
    }
}
