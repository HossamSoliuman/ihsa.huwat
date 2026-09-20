<?php

namespace Database\Factories;

use App\Models\BoatCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoatCategory>
 */
class BoatCategoryFactory extends Factory
{
    protected $model = BoatCategory::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'name_en' => fake()->words(2, true),
            'active' => true,
            'display_order' => fake()->numberBetween(0, 20),
        ];
    }
}
