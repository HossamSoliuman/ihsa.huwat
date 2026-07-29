<?php

namespace Database\Factories;

use App\Models\HarborViolation;
use App\Models\Port;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HarborViolation>
 */
class HarborViolationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'port_id' => Port::factory(),
            'violation_number' => fake()->unique()->bothify('VIO-####-??'),
            'violation_type' => fake()->randomElement(['سلامة', 'ترخيص', 'تشغيل']),
            'violation_description' => fake()->sentence(),
            'violation_date' => now(),
            'fine_amount' => fake()->numberBetween(0, 5000),
            'violation_status' => 'open',
            'created_by' => User::factory(),
        ];
    }
}
