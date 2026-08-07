<?php

namespace Database\Factories;

use App\Models\FishMarketUnit;
use App\Models\FishMarketWorker;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishMarketWorker> */
class FishMarketWorkerFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'fish_market_unit_id' => FishMarketUnit::factory(),
            'full_name' => fake()->name(),
            'national_id' => '1'.fake()->unique()->numerify('#########'),
            'phone' => '05'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            /** Codes, never names — the shipped defaults are what the seeded lists hold. */
            'nationality' => array_key_first((array) config('information.nationalities')),
            'job_title' => array_key_first((array) config('information.market_job_titles')),
        ];
    }
}
