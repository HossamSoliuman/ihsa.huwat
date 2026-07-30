<?php

namespace Database\Factories;

use App\Models\Region;
use App\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-4 months', '+6 months');

        return [
            'region_id' => Region::factory(),
            'name' => 'موسم '.fake()->unique()->word(),
            'status' => fake()->randomElement(array_keys(config('government.season_statuses'))),
            'start_date' => $startDate,
            'end_date' => fake()->dateTimeBetween($startDate, '+10 months'),
            'fishing_tools' => fake()->randomElements(config('government.fishing_tool_options'), 2),
            'licenses_count' => fake()->numberBetween(10, 500),
            'minimum_size' => fake()->randomFloat(2, 10, 20),
            'maximum_size' => fake()->randomFloat(2, 21, 40),
            'restrictions' => fake()->sentence(),
        ];
    }
}
