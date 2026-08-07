<?php

namespace Database\Factories;

use App\Models\FishMarket;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FishMarket> */
class FishMarketFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name' => 'سوق السمك المركزي '.fake()->unique()->numerify('###'),
            'investor_name' => 'مؤسسة '.fake()->unique()->lastName().' للأسماك',
            'investor_commercial_registration_no' => fake()->unique()->numerify('10########'),
            'investor_phone' => '05'.fake()->numerify('########'),
            'investor_email' => fake()->unique()->safeEmail(),
            'investor_tax_number' => fake()->numerify('3###########03'),
            'investor_national_address' => fake()->streetName().' — '.fake()->city(),
            'is_active' => true,
        ];
    }

    /** Switched off: kept on record, hidden from the lists the desk offers. */
    public function retired(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
