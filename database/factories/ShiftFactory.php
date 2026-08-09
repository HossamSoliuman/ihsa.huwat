<?php

namespace Database\Factories;

use App\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('shift_????####'),
            'name' => 'مناوبة '.fake()->unique()->numerify('###'),
            'start_time' => '06:00:00',
            'end_time' => '14:00:00',
            'crosses_midnight' => false,
            'grace_minutes' => 15,
            'is_active' => true,
        ];
    }
}
