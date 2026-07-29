<?php

namespace Database\Factories;

use App\Models\Captain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Captain>
 */
class CaptainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'national_id' => fake()->unique()->numerify('##########'),
            'phone' => '05'.fake()->numerify('########'),
        ];
    }
}
