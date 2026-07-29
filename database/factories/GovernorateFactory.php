<?php

namespace Database\Factories;

use App\Models\Governorate;
use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Governorate> */
class GovernorateFactory extends Factory
{
    public function definition(): array
    {
        return ['region_id' => Region::factory(), 'name' => fake()->unique()->city()];
    }
}
