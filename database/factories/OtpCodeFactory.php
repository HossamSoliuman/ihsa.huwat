<?php

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '05'.fake()->numerify('########'),
            'code_hash' => Hash::make('123456'),
            'purpose' => OtpCode::PURPOSE_PASSWORD_RESET,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(OtpCode::TTL_MINUTES),
            'verified_at' => null,
            'used_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['expires_at' => now()->subMinute()]);
    }
}
