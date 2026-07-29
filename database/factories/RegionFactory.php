<?php

namespace Database\Factories;

use App\Models\Region;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Region> */
class RegionFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->unique()->city().' الإدارية'];
    }
}
