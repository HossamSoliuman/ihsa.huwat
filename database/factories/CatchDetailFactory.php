<?php

namespace Database\Factories;

use App\Models\CatchDetail;
use App\Models\FishSpecies;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatchDetail>
 */
class CatchDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory(),
            'species_id' => FishSpecies::factory(),
            'captain_reported_kg' => 100,
            'verified_kg' => 105,
            'boxes_count' => 10,
            'is_unreported_by_captain' => false,
        ];
    }
}
