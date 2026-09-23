<?php

namespace Database\Factories;

use App\Models\RegistrationRequest;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RegistrationRequest>
 */
class RegistrationRequestFactory extends Factory
{
    protected $model = RegistrationRequest::class;

    public function definition(): array
    {
        return [
            'role_id' => Role::firstOrCreate(['key' => Role::OWNER], Role::factory()->key(Role::OWNER)->raw())->id,
            'name' => fake()->name(),
            'phone' => '05'.fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'business_name' => fake()->company(),
            'city' => fake()->city(),
            'boats_count' => fake()->numberBetween(1, 12),
            'notes' => null,
            'password' => 'secret-123',
            'status' => RegistrationRequest::PENDING,
        ];
    }

    public function dalal(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['key' => Role::DALAL], Role::factory()->key(Role::DALAL)->raw())->id,
            'boats_count' => null,
        ]);
    }
}
