<?php

namespace Database\Factories;

use App\Models\CatchRecord;
use App\Models\Species;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatchRecord>
 */
class CatchRecordFactory extends Factory
{
    protected $model = CatchRecord::class;

    public function definition(): array
    {
        $kg = fake()->randomFloat(2, 10, 300);

        return [
            'trip_id' => Trip::factory(),
            'species_id' => Species::factory(),
            'quantity_kg' => $kg,
            'captain_kg' => $kg,
            'recorded_at' => now()->toDateString(),
        ];
    }
}
