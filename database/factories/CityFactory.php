<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<City> */
class CityFactory extends Factory
{
    public function definition(): array
    {
        return ['governorate_id' => Governorate::factory(), 'name' => fake()->unique()->city()];
    }
}
