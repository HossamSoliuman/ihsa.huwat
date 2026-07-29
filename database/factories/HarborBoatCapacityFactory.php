<?php

namespace Database\Factories;

use App\Models\HarborBoatCapacity;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HarborBoatCapacity>
 */
class HarborBoatCapacityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'boat_type' => fake()->randomElement(['large', 'small', 'recreational']),
            'capacity' => fake()->numberBetween(5, 100),
            'status' => 'available',
        ];
    }
}
