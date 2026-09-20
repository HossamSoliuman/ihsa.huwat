<?php

namespace Database\Factories;

use App\Models\Species;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Species>
 */
class SpeciesFactory extends Factory
{
    protected $model = Species::class;

    public function definition(): array
    {
        return [
            'name_ar' => 'صنف '.fake()->unique()->numerify('####'),
            'name_sci' => ucfirst(fake()->lexify('??????')).' '.fake()->lexify('??????'),
            'name_en' => fake()->words(2, true),
        ];
    }
}
