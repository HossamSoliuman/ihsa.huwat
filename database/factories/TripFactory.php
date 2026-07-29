<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Captain;
use App\Models\Port;
use App\Models\Trip;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trip>
 */
class TripFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_code' => fake()->unique()->bothify('TRIP-#####'),
            'boat_id' => Boat::factory(),
            'captain_id' => Captain::factory(),
            'port_id' => Port::factory(),
            'expected_arrival' => now(),
            'status' => 'expected',
        ];
    }
}
