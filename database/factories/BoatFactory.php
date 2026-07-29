<?php

namespace Database\Factories;

use App\Models\Boat;
use App\Models\Port;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Boat>
 */
class BoatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'قارب '.fake()->unique()->numerify('####'),
            'registration_no' => fake()->unique()->bothify('B-####-??'),
            'boat_type' => 'small',
            'harbor_status' => 'occupied',
            'home_port_id' => Port::factory(),
        ];
    }
}
