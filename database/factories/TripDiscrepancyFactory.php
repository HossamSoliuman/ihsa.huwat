<?php

namespace Database\Factories;

use App\Models\Trip;
use App\Models\TripDiscrepancy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TripDiscrepancy>
 */
class TripDiscrepancyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trip_id' => Trip::factory()->state(['status' => 'pending_review', 'actual_arrival' => now()]),
            'diff_kg' => fake()->randomFloat(2, 1, 500),
            'diff_percent' => fake()->randomFloat(2, 3, 30),
            'severity' => 'medium',
            'reason' => fake()->sentence(4),
            'review_status' => 'pending',
        ];
    }
}
