<?php

namespace Database\Factories;

use App\Models\Governorate;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Port>
 */
class PortFactory extends Factory
{
    protected $model = Port::class;

    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name' => 'ميناء '.fake()->unique()->numerify('####'),
            'lat' => fake()->latitude(16, 28),
            'lng' => fake()->longitude(35, 50),
        ];
    }
}
