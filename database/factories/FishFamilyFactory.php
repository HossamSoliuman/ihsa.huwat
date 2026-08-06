<?php

namespace Database\Factories;

use App\Models\FishFamily;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FishFamily>
 */
class FishFamilyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            /** Families sit on the hundreds of the coding sheet: 1000, 1100, 1200… */
            'code' => fake()->unique()->numberBetween(10, 650) * 100,
            'scientific_name' => fake()->unique()->words(2, true),
            'english_name' => fake()->words(2, true),
            'local_name_gulf' => 'عائلة '.fake()->unique()->numerify('###'),
            'local_name_red_sea' => 'عائلة '.fake()->unique()->numerify('###'),
        ];
    }
}
