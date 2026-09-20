<?php

namespace Database\Factories;

use App\Models\TripType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripType>
 */
class TripTypeFactory extends Factory
{
    protected $model = TripType::class;

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
