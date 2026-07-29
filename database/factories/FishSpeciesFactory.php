<?php

namespace Database\Factories;

use App\Models\FishSpecies;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FishSpecies>
 */
class FishSpeciesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'نوع سمك '.fake()->unique()->numerify('####'),
        ];
    }
}
