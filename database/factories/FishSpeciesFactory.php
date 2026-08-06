<?php

namespace Database\Factories;

use App\Models\FishFamily;
use App\Models\FishSpecies;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FishSpecies>
 */
class FishSpeciesFactory extends Factory
{
    /**
     * Define the model's default state: a species added by hand at the desk, which is
     * the one case the coding sheet leaves without a code.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_ar' => 'نوع سمك '.fake()->unique()->numerify('####'),
        ];
    }

    /** Filed under the national coding sheet, the way every seeded species is. */
    public function coded(): static
    {
        return $this->state(fn (): array => [
            'fish_family_id' => FishFamily::factory(),
            'code' => fake()->unique()->numberBetween(1001, 6499),
            'scientific_name' => fake()->words(2, true),
            'english_name' => fake()->words(2, true),
            'local_name_gulf' => 'اسم خليجي '.fake()->unique()->numerify('###'),
            'local_name_red_sea' => 'اسم أحمر '.fake()->unique()->numerify('###'),
        ]);
    }
}
