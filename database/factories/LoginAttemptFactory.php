<?php

namespace Database\Factories;

use App\Models\LoginAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LoginAttempt> */
class LoginAttemptFactory extends Factory
{
    public function definition(): array
    {
        return ['username' => fake()->userName(), 'ip_address' => fake()->ipv4(), 'success' => false];
    }
}
