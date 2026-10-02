<?php

namespace Database\Factories;

use App\Models\CounterApplication;
use App\Models\HiringRound;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CounterApplication>
 */
class CounterApplicationFactory extends Factory
{
    protected $model = CounterApplication::class;

    public function definition(): array
    {
        return [
            'hiring_round_id' => HiringRound::factory(),
            'operating_company_id' => fn (array $attributes) => HiringRound::find($attributes['hiring_round_id'])->operating_company_id,
            'port_id' => fn (array $attributes) => HiringRound::find($attributes['hiring_round_id'])->port_id,
            'token' => Str::random(48),
            'name' => fake()->name(),
            'phone' => '05'.fake()->unique()->numerify('########'),
            'national_id' => '1'.fake()->numerify('#########'),
            'email' => null,
            'experience_years' => fake()->numberBetween(0, 10),
            'password' => 'secret-123',
            'phone_verified_at' => now(),
            'status' => CounterApplication::PENDING,
        ];
    }

    public function inRound(HiringRound $round): static
    {
        return $this->state(fn () => [
            'hiring_round_id' => $round->id,
            'operating_company_id' => $round->operating_company_id,
            'port_id' => $round->port_id,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['phone_verified_at' => null]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => CounterApplication::APPROVED, 'reviewed_at' => now()]);
    }
}
