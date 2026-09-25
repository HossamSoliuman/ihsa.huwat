<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '05'.fake()->unique()->numerify('########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * حساب بدور من أدوار التطبيق؛ الدور يُنشأ إن لم يكن مبذورًا.
     */
    public function role(string $key): static
    {
        return $this->state(fn () => [
            'role_id' => Role::firstOrCreate(['key' => $key], Role::factory()->key($key)->raw())->id,
        ]);
    }

    public function superAdmin(): static
    {
        return $this->role(Role::SUPER_ADMIN);
    }

    public function owner(): static
    {
        return $this->role(Role::OWNER);
    }

    public function dalal(): static
    {
        return $this->role(Role::DALAL);
    }

    /**
     * حساب تابع لمالك (كابتن، طاقم، موظف).
     */
    public function ownedBy(User $owner): static
    {
        return $this->state(fn () => ['owner_id' => $owner->id]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['active' => false]);
    }
}
