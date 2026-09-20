<?php

namespace Database\Factories;

use App\Models\FisherRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FisherRole>
 */
class FisherRoleFactory extends Factory
{
    protected $model = FisherRole::class;

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
