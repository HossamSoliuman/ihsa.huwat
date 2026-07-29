<?php

namespace Database\Factories;

use App\Models\Governorate;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Port> */
class PortFactory extends Factory
{
    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name' => 'ميناء '.fake()->unique()->city(),
            'location_name' => fake()->streetName(),
            'is_active' => true,
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }
}
